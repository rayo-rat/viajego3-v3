#!/bin/bash

# --- PASO 1: ESPERAR A QUE EL MAESTRO ESTÉ LISTO ---
echo "Waiting for db-master:3306 to be ready..."

# Bucle para esperar hasta que el maestro esté accesible
while ! mysqladmin ping -h"db-master" -P"3306" --silent; do
    echo "db-master not ready, sleeping..."
    sleep 3
done

echo "db-master is ready. Proceeding with replication setup."

# El script de replicación necesita esperar a que el propio esclavo se inicie completamente.
# El entrypoint de Docker de MySQL se encargará de inicializar los datos si es la primera vez.
# Si llega a este punto, MySQL en el esclavo ya está corriendo.

# --- PASO 2: CREAR USUARIO DE REPLICACIÓN EN EL MAESTRO (Si no está en init.sql) ---
# Ejecutar solo si el maestro está listo.
mysql -h"db-master" -uroot -p"root_password_segura" -e "
CREATE USER IF NOT EXISTS 'replica_user'@'%' IDENTIFIED BY 'replica_password';
GRANT REPLICATION SLAVE ON *.* TO 'replica_user'@'%';
FLUSH PRIVILEGES;
"
echo "Replication user created on db-master."

# --- PASO 3: OBTENER POSICIÓN DEL BINLOG DEL MAESTRO ---
MASTER_LOG_FILE=$(mysql -h"db-master" -uroot -p"root_password_segura" -e "SHOW MASTER STATUS\G" | grep File: | awk '{print $2}')
MASTER_LOG_POS=$(mysql -h"db-master" -uroot -p"root_password_segura" -e "SHOW MASTER STATUS\G" | grep Position: | awk '{print $2}')

echo "Master Log File: $MASTER_LOG_FILE"
echo "Master Log Position: $MASTER_LOG_POS"

# --- PASO 4: CONFIGURAR Y EMPEZAR LA REPLICACIÓN EN EL ESCLAVO ---
mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "
CHANGE REPLICATION SOURCE TO
  SOURCE_HOST='db-master',
  SOURCE_USER='replica_user',
  SOURCE_PASSWORD='replica_password',
  SOURCE_LOG_FILE='$MASTER_LOG_FILE',
  SOURCE_LOG_POS=$MASTER_LOG_POS,
  SOURCE_AUTO_POSITION=0;
START REPLICA;
"

echo "Replication configured and started on mysql-slave."

# Este script finaliza, permitiendo que el contenedor siga ejecutando el proceso mysqld.