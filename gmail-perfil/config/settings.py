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
class Settings:
    db: DatabaseConfig = DatabaseConfig()

# Instantzia globala
settings = Settings()