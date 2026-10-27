class Erabiltzaileak:

    def __init__(self,id: int,username: str,password: str,izena: str,abizena: str,
        hiria: str,lurraldea: str,herrialdea: str,postakodea: int,telefonoa: int, irudia: str
    ):
        self.id = id
        self.username = username
        self.password = password
        self.izena = izena
        self.abizena = abizena
        self.hiria = hiria
        self.lurraldea = lurraldea
        self.herrialdea = herrialdea
        self.postakodea = postakodea
        self.telefonoa = telefonoa
        self.irudia = irudia

    def argazkia_du(self) -> bool:
        if self.irudia is None or self.irudia.strip() == "":
            return False
        return True