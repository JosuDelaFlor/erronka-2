
from services.smtp_client import SMTPClient
from services.mysql_client import MySQLClient
from config.settings import settings
from model.erabiltzaileak import Erabiltzaileak


def main() -> None:

    print("Programa abiarazten...")

    try:
        # Datu-basera konektatu
        db = MySQLClient(settings.db)

        print("Datu-basetik erabiltzaileak lortzen...")

        # Erabiltzaile guztiak lortu
        erabiltzaileak = db.fetch_all("SELECT * FROM users")

        print(f"{len(erabiltzaileak)} erabiltzaile aurkitu dira.")

        # SMTP bezeroa sortu
        smtp = SMTPClient()

        bidalitakoak = 0

        # Erabiltzaile guztiak aztertu
        for datuak in erabiltzaileak:

            erabiltzailea = Erabiltzaileak(**datuak)

            print(f"Erabiltzailea: {erabiltzailea.username}")

            # Argazkia duen egiaztatu
            if erabiltzailea.argazkia_du():
                print("Argazkia dauka. Ez da emailik bidaliko.")

            else:
                print("Ez dauka argazkirik. Emaila bidaltzen...")

                try:
                    smtp.bidali_emaila(
                        erabiltzailea.username,
                        erabiltzailea.izena
                    )

                    bidalitakoak += 1

                    print("Emaila ondo bidali da!")

                except Exception as error:
                    print(f"Errorea emaila bidaltzean: {error}")

        print(f"\nGuztira {bidalitakoak} email bidali dira.")
        print("Programa amaitu da.")

    except Exception as error:
        print(f"Errorea: {error}")


if __name__ == "__main__":
    main()
