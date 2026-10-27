
from services.smtp_client import SMTPClient


def main() -> None:

    print("Programa abiarazten...")

    try:
        # SMTP bezeroa sortu
        smtp = SMTPClient()

        print("SMTP zerbitzarira konektatzen...")

        # Probako emaila bidali
        smtp.bidali_emaila(
            "prueba@uni.eus",
            "Ander"
        )

        print("Emaila ondo bidali da!")

    except Exception as error:
        print(f"Errorea emaila bidaltzean: {error}")


if __name__ == "__main__":
    main()
