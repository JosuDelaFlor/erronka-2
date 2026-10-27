#hemen definitzen dira datu baseko eta SMTP zerbitzuko konfigurazioak, honen bidez MySQL datu basearekin konektatu eta emailak bidaltzeko erabiltzaileei, profilean argazkirik ez dutenak.
import os

from dataclasses import dataclass

@dataclass(frozen=True)
class DatabaseConfig:
    host: str = os.getenv("DB_HOST", "db")
    port: int = int(os.getenv("DB_PORT", 3306))
    user: str = os.getenv("DB_USER", "root")
    password: str = os.getenv("DB_PASSWORD", "rootpassword")
    database: str = os.getenv("DB_NAME", "ziber_db")

@dataclass(frozen=True)
class SMTPConfig:
    host: str = os.getenv("SMTP_HOST", "smtp4dev")
    port: int = int(os.getenv("SMTP_PORT", 25))
    sender: str = os.getenv("SMTP_SENDER", "avisos@ziber.local")
    timeout: int = int(os.getenv("SMTP_TIMEOUT", 15))

@dataclass(frozen=True)
class Settings:
    db: DatabaseConfig = DatabaseConfig()
    smtp: SMTPConfig = SMTPConfig()

settings = Settings()