import re
import csv

def pick(pattern, text, default=""):
    m = re.search(pattern, text, flags=re.I)
    return (m.group(1).strip() if m else default)

def pick_tax(block, tax_name):
    # procura a seção do imposto e tenta achar o primeiro "Valor\nX"
    # limita a busca a um trecho para não capturar "Valor" de ICMS etc.
    m = re.search(rf"{tax_name}\s*\n(.{{0,800}}?)\n(?:[A-ZÀ-Ü]|$)", block, flags=re.I|re.S)
    if not m:
        return "0,00"
    chunk = m.group(1)
    v = pick(r"Valor\s*\n([\d.,]+)", chunk, default="")
    return v if v else "0,00"

with open("entrada.txt", "r", encoding="utf-8") as f:
    raw = f.read()

# normaliza espaços “esquisitos”
text = raw.replace("\u00a0", " ").replace("\r\n", "\n").replace("\r", "\n")

# número da nota
numero_nota = pick(r"\nNúmero\s*\n(\d+)\n", text, default="")

# acha o início de cada item (linha só com número do item)
item_starts = [m.start() for m in re.finditer(r"\n(\d{1,3})\n", text)]
# filtra para não pegar o "Número 5480079" lá em cima:
# (mantém só os que têm descrição logo depois)
valid_starts = []
for pos in item_starts:
    snippet = text[pos:pos+200]
    if re.search(r"\n\d{1,3}\n[^\n]+\n[\d,]+\n[A-Z]{1,4}\n[\d,]+\n", snippet):
        valid_starts.append(pos)

# monta blocos
blocks = []
for i, pos in enumerate(valid_starts):
    end = valid_starts[i+1] if i+1 < len(valid_starts) else len(text)
    blocks.append(text[pos:end])

rows = []
for block in blocks:
    # cabeçalho do item
    mhead = re.search(r"\n(\d{1,3})\n([^\n]+)\n([\d,]+)\n([A-Z]{1,4})\n([\d,]+)\n", block)
    if not mhead:
        continue

    desc = mhead.group(2).strip()

    cod_prod = pick(r"Código do Produto\s*\n([0-9]+)", block, default="")
    ean_com  = pick(r"Código EAN Comercial\s*\n([0-9]+)", block, default="")
    un_com   = pick(r"Unidade Comercial\s*\n([A-Z0-9]+)", block, default="")
    qtd_com  = pick(r"Quantidade Comercial\s*\n([\d,]+)", block, default="")
    vun_com  = pick(r"Valor unitário de comercialização\s*\n([\d,]+)", block, default="")
    num_ped  = pick(r"Número do pedido de compra\s*\n([0-9]+)", block, default="")
    item_ped = pick(r"Item do pedido de compra\s*\n([0-9]+)", block, default="")

    v_icmsst = pick(r"Valor do ICMS ST\s*\n([\d,]+)", block, default="0,00") or "0,00"
    v_ipi    = pick(r"Valor IPI\s*\n([\d,]+)", block, default="0,00") or "0,00"

    v_pis    = pick_tax(block, "PIS")
    v_cofins = pick_tax(block, "COFINS")

    rows.append([
        numero_nota,
        cod_prod,
        ean_com,
        un_com,
        desc,
        qtd_com,
        vun_com,
        num_ped,
        v_pis,
        v_cofins,
        v_icmsst,
        v_ipi,
        item_ped
    ])

# CSV
csv_header = [
    "Número Nota","Código do Produto","Código EAN Comercial","Unidade Comercial","Descrição",
    "Quantidade Comercial","Valor unitário de comercialização","Número do pedido de compra",
    "Valor Pis","Valor Cofins","Valor do ICMS ST","Valor IPI","Item do pedido de compra"
]
with open("saida.csv", "w", encoding="utf-8", newline="") as f:
    w = csv.writer(f, delimiter=";")
    w.writerow(csv_header)
    w.writerows(rows)

# Markdown
def md_escape(s): return str(s).replace("|", "\\|")
with open("saida.md", "w", encoding="utf-8") as f:
    f.write("| " + " | ".join(csv_header) + " |\n")
    f.write("|" + "|".join(["---"]*len(csv_header)) + "|\n")
    for r in rows:
        f.write("| " + " | ".join(md_escape(x) for x in r) + " |\n")

print(f"OK: {len(rows)} itens -> saida.csv e saida.md")
