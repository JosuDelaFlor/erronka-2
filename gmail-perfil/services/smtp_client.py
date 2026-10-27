import smtplib
from email.message import EmailMessage
from config.settings import SMTPConfig

class SMTPClient:
    def __init__(self, config: SMTPConfig) -> None:
        self.config = config

    def bidali_emaila(self, emaila: str, izena: str) -> None:
        mezua = EmailMessage()
        mezua["From"] = self.config.sender
        mezua["To"] = emaila
        mezua["Subject"] = "Profileko argazkia derrigorrezkoa da"
        
        mezua.set_content(
            f"Kaixo {izena},\n\n"
            "Zure profilean oraindik ez duzu argazkirik igo.\n"
            "Argazkia derrigorrezkoa da.\n"
            "Mesedez, saioa hasi eta igo ezazu argazkia ahal duzunean.\n\n"
            "Eskerrik asko."
        )

        with smtplib.SMTP(self.config.host, self.config.port, timeout=self.config.timeout) as smtp:
            smtp.send_message(mezua)