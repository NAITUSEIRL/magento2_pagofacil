#####Instrucciones para habilitar y configurar modulo
1. Copiar la carpeta "NaitusEirl" a "app/code"
2. Habilitar el modulo con el comando **php bin/magento module:enable NaitusEirl_PagoFacil**
3. Correr instaladores con el comando **php bin/magento setup:upgrade**
3. Ir al panel de administrador
4. Acceder a Stores -> Configuration -> Sales -> Payment Methods -> Pago Facil
5. Llenar al menos "Token Servicio", "Token Secret" y activar el metodo de pago.
6. Limpiar caches