import random
import string

CANTIDAD = 1000

# Función para generar una contraseña aleatoria
def generate_password(length=8):
    characters = string.ascii_letters + string.digits
    return ''.join(random.choice(characters) for _ in range(length))

# Genera CANTIDAD usuarios con contraseñas
users = []
for i in range(CANTIDAD):
    username = f"user{i + 1}"
    password = generate_password()
    users.append(f"{username},{password}")

# Guarda los usuarios en un archivo de texto
with open("data.txt", "w") as file:
    file.write("\n".join(users))

print("Usuarios y contraseñas generados y guardados en users.txt.")
