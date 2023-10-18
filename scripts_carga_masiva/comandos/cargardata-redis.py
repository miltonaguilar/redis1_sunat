import random
import string
import time
import redis

# Conecta con el servidor de Redis
r = redis.Redis(
  host='redis-18871.c13.us-east-1-3.ec2.cloud.redislabs.com',
  port=18871,
  password='ouu9GVbgA4rstxME9ZgqqMGFaseZ6Env')

def generate_hash(length=16):
    characters = string.ascii_letters + string.digits
    return ''.join(random.choice(characters) for _ in range(length))

# Abre el archivo de datos
with open('data.txt', 'r') as file:
    # Lee línea por línea y carga los datos en Redis
    for line in file:
        username, password = line.strip().split(',')
        
        if r.hget("users", username):
            print("Lo siento, el usuario {} seleccionado ya está en uso.".format(username))
        
        #Se obtiene el siguiente userid
        userid = r.incr("next_user_id")
        authsecret = generate_hash()

        #Se añade el usuario a la key users
        r.hset("users", username, userid)

        datos = {
            "username": username,
            "password": password,
            "auth": authsecret
        }

        #Se crea el registro del user:<CORRELATIVO>
        r.hmset("user:{}".format(userid),datos)
        
        #Se crea un nuevo registro en la key auths
        r.hset("auths", authsecret, userid)

        #Se crea un registro en la key users_by_time
        r.zadd("users_by_time", {username: int(time.time())})

# Cierra la conexión a Redis
r.connection_pool.disconnect()

print("Datos cargados exitosamente en Redis.")
