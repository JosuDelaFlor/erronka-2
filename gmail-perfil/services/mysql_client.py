import mysql.connector

from mysql.connector.connection import MySQLConnection
from typing import Any, Dict, List, Optional
from config.settings import DatabaseConfig

class MySQLClient:
    def __init__(self, config: DatabaseConfig) -> None:
        self.config = config
        self._connection: Optional[MySQLConnection] = None

    def _connect(self) -> None:
        """Establece la conexión si no está activa."""
        if not self._connection or not self._connection.is_connected():
            self._connection = mysql.connector.connect(
                host=self.config.host,
                port=self.config.port,
                user=self.config.user,
                password=self.config.password,
                database=self.config.database
            )

    def _disconnect(self) -> None:
        """Cierra la conexión activa."""
        if self._connection and self._connection.is_connected():
            self._connection.close()

    def fetch_all(self, query: str, params: Optional[tuple] = None) -> List[Dict[str, Any]]:
        """Ejecuta una consulta SELECT y retorna una lista de diccionarios."""
        self._connect()
        cursor = self._connection.cursor(dictionary=True)
        try:
            cursor.execute(query, params or ())
            result: List[Dict[str, Any]] = cursor.fetchall()
            return result
        finally:
            cursor.close()
            self._disconnect()