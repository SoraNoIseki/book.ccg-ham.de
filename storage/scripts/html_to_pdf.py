# scripts/html_to_pdf.py
import sys
from weasyprint import HTML

def main():
    # 仅需要 input.html 和 output.pdf
    if len(sys.argv) != 3:
        print("Usage: html_to_pdf.py input.html output.pdf", file=sys.stderr)
        sys.exit(1)

    input_html = sys.argv[1]
    output_pdf = sys.argv[2]

    # 生成 PDF
    HTML(filename=input_html).write_pdf(output_pdf)

    # 可选：输出调试信息
    print(f"PDF generated: {output_pdf}", file=sys.stderr)

if __name__ == "__main__":
    main()
