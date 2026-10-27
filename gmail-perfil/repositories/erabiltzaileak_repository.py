from typing import List
from services.mysql_client import MySQLClient
from model.erabiltzaileak import Erabiltzaileak

class ErabiltzaileakRepository:
    def __init__(self, db_client: MySQLClient) -> None:
        self.db_client = db_client

    def lortu_guztiak(self) -> List[Erabiltzaileak]:
        query = "SELECT * FROM users"
        emaitzak = self.db_client.fetch_all(query)
        
        erabiltzaileak = []
        for datuak in emaitzak:
            erabiltzaileak.append(Erabiltzaileak(**datuak))
            
        return erabiltzaileak