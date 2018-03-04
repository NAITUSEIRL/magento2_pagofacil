##### Instrucciones para habilitar y configurar modulo
* Copiar la carpeta "NaitusEirl" a "app/code"
* Copiar la carpeta "ctala" a "lib/internal/"
* Habilitar el modulo con el comando **php bin/magento module:enable NaitusEirl_PagoFacil**
* Correr instaladores con el comando **php bin/magento setup:upgrade**
* Ir al panel de administrador
* Acceder a Stores -> Configuration -> Sales -> Payment Methods -> Pago Facil
* Llenar al menos "Token Servicio", "Token Secret" y activar el metodo de pago.
* Limpiar caches

##### Otras instrucciones de instalación:
[https://belvg.com/blog/how-to-install-module-manually-on-magento-2.html](https://belvg.com/blog/how-to-install-module-manually-on-magento-2.html)
