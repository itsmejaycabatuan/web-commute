import argparse
import pdfplumber
import json

parser = argparse.ArgumentParser()
parser.add_argument("pdf")
# parser.add_argument("x")
# parser.add_argument("y")
args = parser.parse_args()

with pdfplumber.open(args.pdf) as pdf:
    first_page = pdf.pages[0]
    fetch = first_page.extract_table()

    table = []

    for i in fetch:
        km = i[0]
        regular = i[1]
        discount = i[2]
        table.append({
            'km': km,
            'regular': regular,
            'discount': discount
        })

    for i in fetch:
        km = i[3]
        regular = i[4]
        discount = i[5]
        table.append({
            'km': km,
            'regular': regular,
            'discount': discount
        })

    print (json.dumps(table));



    # print(fetxh[int(args.x)][int(args.y)])