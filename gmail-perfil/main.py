from config.settings import settings
from services.mysql_client import MySQLClient

def main() -> None:
    # Instanciamos el cliente inyectando la configuración (Inversión de Dependencias)
    db_client = MySQLClient(config=settings.db)
    
    # Definimos la consulta basándonos en tu estructura de la tabla 'users'
    query = "SELECT id, username, izena, irudia FROM users;"
    
    try:
        print("Conectando a la base de datos y ejecutando la consulta...")
        # Ejecutamos el método fetch_all que hemos creado
        users = db_client.fetch_all(query)
        
        if not users:
            print("No se encontraron usuarios en la base de datos.")
            return

        print(f"\n--- Se encontraron {len(users)} usuarios ---")
        for user in users:
            print(f"ID: {user['id']} | Username: {user['username']} | Imagen: {user['irudia']}")
            
    except Exception as e:
        print(f"Error de conexión o ejecución en la base de datos: {e}")

if __name__ == "__main__":
    main()