# Celdas
## Trabajo Practico 02: Contratos Inteligentes

### Correr el proyecto

Para configurarlo en un principio debemos deployar el contrato en alguna red de etherium (fue probado en un workspace de Ganache) y cambiar la dirección del contrato en el archivo '/client/src/config.js'.

Luego corremos el cliente:
Para esto debemos tener instalado node y luego correr los siguientes comandos:

~~~bash
	cd client
	npm i
	npm start
~~~

Y automaticamente se corre en localhost:3000



### Objetivo:
Se requiere crear un contrato inteligente en solidity. Desplegar el mismo en un nodo local con
Ganache y crear una interfaz Web de acceso utilizando MetaMask para manejar la seguridad de las
billeteras.

Si el alumno lo prefiere, podrá utilizar una de las redes de prueba de Ethereum, en lugar de Ganache
(ropsten kovan rinkeby).

### Detalle y requerimientos:

Se trata de crear un contrato inteligente que permita gestionar un sitio de financiamiento colectivo
(crowdfunding), al igual que https://www.idea.me/ o https://www.kickstarter.com.
Este trabajo tendrá dos partes, una exclusivamente hecha en Solidity y desplegada en la blockchain
y otra Web, que permitirá interactuar con el contrato ya desplegado.

Parte de Solidity:
Tendrá que haber un struct Proyecto con los siguientes atributos (pueden poner los nombres en
inglés si lo prefieren):
-Nombre del proyecto
-Monto requerido (en ethers o weis)
-Fecha de creación
-Fecha de cierre
-Aportantes (Mapa de Address => monto)
-Aportantes Array de Address
-Estado: cerrado, cancelado, en curso. (Enum)


A su vez, habrá un contrato FabricaDeProyectos que permitirá crear proyectos. Ver más abajo el
ejemplo.
A su vez el contrato (FabricaDeProyectos u otro que lo extienda) deberá tener métodos que
permitan hacer las siguientes acciones:
• Crear un nuevo proyecto (ver ejemplo)
• Devolver todos los proyectos
• Aportar dinero a un proyecto (este método deberá ser payable y debe recibir un ID de
proyecto). Deberá además registrar al aportarte en el Proyecto y transferir el dinero (ethers)
a la cuenta del proyecto.
• Verificar el dinero actual en un proyecto dado su ID. (verificar también que ese dinero existe
en la cuenta del contrato.)
• Dado un proyecto según su ID, verificar todo el dinero aportado, si es mayor igual al monto
requerido poner el proyecto en estado cerrado. Si es menor verificar si la fecha actual es
mayor a la de cierre. Si la fecha es mayor poner el proyecto en estado cancelado.
En ambos casos emitir un evento.
Si el proyecto es cerrado, transferir el dinero de la cuenta del contrato a la cuenta del
creador del contrato. Si el proyecto es cancelado devolver el dinero a cada aportarte.


### Parte Web:

Se espera usar una Web muy sencilla, dos página nada más. La primera debe tener un formulario de
creación y un botón crear que llama al método crear proyecto en la blockchain y usando metaMask
lo crea.
La segunda página o sección, es una lista de los proyectos creados, mostrando el id, nombre, fecha
creación, fecha fin, el monto requerido y el monto obtenido. Cada proyecto debe tener dos botones:
-Aportar que me permita aportar dinero al proyecto.
-Verificar que llamará al método que verifica si la fecha es mayor o menor y realiza las acciones ya
descriptas.


### Entrega:

Fecha de entrega: 13 de noviembre de 2019.
Lo que se espera es que cada grupo entregue el código fuente:
• Archivo(s) de extensión “.sol” escrito en solidity
• Archivos que conformen la interfaz Web de acceso.
• Documento indicando los pasos necesarios para correrlo si es que requiere alguna
configuración particular.
Presentación:
El sistema deberá mostrarse corriendo y funcionando ante alguno de los docentes de la materia.
Material de ayuda:
Curso on-line interactivo, en español de Solidity y contratos inteligentes:
https://cryptozombies.io/es/course/
Vídeo tutoriales sobre solidty y aplicaciones descentralizadas (Dapps):
https://www.youtube.com/channel/UCY0xL8V6NzzFcwzHCgB8orQ
Para ayudarse en el desarrollo de este trabajo, podrán utilizar la IDE Remix:
http://remix.ethereum.org/
Podrán encontrar un tutorial breve pero conciso de como utilizar dicha IDE en el siguiente vídeo de
youtube: https://www.youtube.com/watch?v=7I4E78_-B4M
Más información en la documentación oficial: https://solidity.readthedocs.io/en/v0.4.24/