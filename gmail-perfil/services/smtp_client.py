
import os
import smtplib
from email.message import EmailMessage


class SMTPClient:

    def __init__(self):
        # SMTP zerbitzariaren konfigurazioa
        docker = os.path.exists("/.dockerenv")

        self.host = os.getenv(
            "SMTP_HOST",
            "smtp4dev" if docker else "127.0.0.1"
        )

        self.port = int(os.getenv(
            "SMTP_PORT",
            "25" if docker else "2525"
        ))

        self.remitente = "avisos@ziber.local"

    def bidali_emaila(self, emaila: str, izena: str) -> None:

        # Mezua sortu
        mezua = EmailMessage()

        mezua["From"] = self.remitente
        mezua["To"] = emaila
        mezua["Subject"] = "Profileko argazkia derrigorrezkoa da"

        # Emailaren edukia
        mezua.set_content(
            f"Kaixo {izena},\n\n"
            "Zure profilean oraindik ez duzu argazkirik igo.\n"
            "Argazkia derrigorrezkoa da.\n"
            "Mesedez, saioa hasi eta igo ezazu argazkia "
            "ahal duzunean.\n\n"
            "Eskerrik asko."
        )

        # SMTP zerbitzarira konektatu eta emaila bidali
        with smtplib.SMTP(
            self.host,
            self.port,
            timeout=15
        ) as smtp:
            smtp.send_message(mezua)

        print(f"Emaila bidalita: {emaila}")
