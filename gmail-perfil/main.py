#Mainean datu basea konektatu eta erabiltzaileak lortu, argazkirik ez duten erabiltzaileei emailak bidaltzeko.
from config.settings import settings
from services.mysql_client import MySQLClient
from services.smtp_client import SMTPClient
from repositories.erabiltzaileak_repository import ErabiltzaileakRepository

def main() -> None:
    print("Programa abiarazten...")

    try:
        db_client = MySQLClient(config=settings.db)
        smtp_client = SMTPClient(config=settings.smtp)

        erabiltzaileak_repo = ErabiltzaileakRepository(db_client=db_client)

        print("Datu-basetik erabiltzaileak lortzen...")
        
        erabiltzaileak = erabiltzaileak_repo.lortu_guztiak()
        
        print(f"{len(erabiltzaileak)} erabiltzaile aurkitu dira.")

        bidalitakoak = 0

        for erabiltzailea in erabiltzaileak:
            print(f"Erabiltzailea: {erabiltzailea.username}")

            if erabiltzailea.argazkia_du():
                print("Argazkia dauka. Ez da emailik bidaliko.")
            else:
                print("Ez dauka argazkirik. Emaila bidaltzen...")
                try:
                    smtp_client.bidali_emaila(
                        emaila=erabiltzailea.username,
                        izena=erabiltzailea.izena
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