#Ejercicio práctico by Dana V Carreno Nava

Hola, he aquí una versión muy sencilla sobre la actividad propuesta, un sistema web dedicado a la administración de apartados de joyería, se usaron las tecnologías de
Laravel 13, React(Inertia), Mysql, Docker Laravel Sail, Pest

#//////////////
#Cómo correr el proyecto??
Necesitas Docker Desktop en Windows con WSL2 activado
Git

#Pasos
#1.Clonar el repositorio:
{git clone https://github.com/Davacana12/jewelry_dvcn.git
cd jewelry_dvcn}

#2.Crear el archivo de configuración:
{ cp .env.example .env}

#3.Instalar las dependencias de PHP (no hace falta tener PHP instalado, se usa Docker):
{ docker run --rm \
-u "$(id -u):$(id -g)" \
-v "$(pwd):/var/www/html" \
-w /var/www/html \
laravelsail/php84-composer:latest \
composer install --ignore-platform-reqs}

#4.Levantar los contenedores:
bash
{ ./vendor/bin/sail up -d}

#5.Generar la llave de la app y crear las tablas con datos de ejemplo:
bash
{./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed}

#6.Instalar y compilar el frontend:
bash
{./vendor/bin/sail npm install
./vendor/bin/sail npm run build}

#7.Abrir el http://localhost en el navegador

#//////////////
Seguí casi al pie de la letra las intrucciones, solo que en la parte de apartados y abonos tuve muchos conflictos, pues al estar presente en las ventas tuve pensamientos como:
1.Los quince días son muy pocos para ser sinceros, normalmente las personas pagan cada que les dan sus quincenas y van dando pagos, peeero, esta la posibilidad que esten dando pagos muy pequenos pues en los abonos ya no se aplicaba la regla del minimo del 20%
2.Se me ocurrio la regla de que el anticipo siguiera con su regla del minimo del 20% del total de compra, porque pensaba que en el segundo abono si daban (aca dos opciones)
a.otro bono mayor o igual al 20% se les daba un plazo extra de 14 días
b.si daban menos del porcentaje solo les daba un plazo extra de 8 días
Si no pagaban después del plazo tenian 4 días para abonar pero se le cobraba un 2% extra del pendiente que tenian en su apartado como castigo, haciendo que la deuda final "aumentara", el quinto día se le cancela automaticamente. Todo este caso ocasionaba el crear otro estado en estados de apartados ananiendo "en riesgo", en abonos se anadia fecha aplazada porque se registraba la fecha del apartado y además una extra del monto, era fecha extendia. Al analizarlo esta opción contaba con la misma falla de que los plazos se alargarían mucho de 8 días en 8 días siendo el caso minimo pues en el abono solo se le comentaba al usuario, no se le obligaba a dar una cantidad pues eso seria algo "asfixiante"
3.Tomando en cuenta la idea anterior, solo se mantuvo el ën riesgo de estados, los 15 días de default para pagar se mantuvieron peeero se anadio 3 días extra por si el usuario no habia hecho un abono manteniendose en "en riesgo"cobrandoles el monto que dieran pero sumandoles el 2%, así ocasionando que fuera sumando el interes al total de compra, algo tipo coppel, que si cada que te fueras a riesgo la deuda aumentaba.
Me hubiera gustado que el sistema reflejara el precio original de la compra sin deudas junto al precio final si se tenian castigos por incumplimiento de pago, que se viera en el sistema el 1000 pesos original vs el 1800 que hayan generado

#Como use la IA???
Utilicé Claude en navegador y en VS
me ayudo a configurar el entorno WSL, Docker, Laravel 13, Node y en ocasiones github pues no recoraba algunos comandos en linux para git
Me ayudó también a la explicación del proceso de algunas cosas como el porqué de las migraciones, algunos procesos de codificación, el porqué y cómo de las configuraciones
Cambié datos manualmente desde la terminal de ubuntu el mysql para checar el funcionamiento de los estados
Cosas que no sirvieron a la primera fue la busqueda y filtración de apartados ya que no realizaba los mapeos necesarios en el sistema, al igual que en apartado, cuando se creaba el apartado y aunque automaticamente mandaba a abonos (cosa que ya se corrigio para que mande a la página de filtrado de apartados) saliendo de ahí y regresando a apartados no se tenia acceso a abonos, siendo así que no hubiera posibilidad para los abonosm cosa que se corrigio con un boton o seleccionando el nombre; las tablas de busqueda de apartados contaban solo con 6, se anadio otra llamada saldo pendiente se modifico saldo a saldo ya pagado y total a total de compra. Otra cosa que se modificó fue el proceso de pago anteriormente comentado (opcion 2) el cual fue descartadp

Lo que definitivamente mejoraria aparte de la distribución de cada página como lo que es para el usuario y otra para el administrador, el tener más tiempo para poder pulir e implementar el método de pago "perfecto" en el cual puedan gastar su dinero sin que se sientan forzados a, pero sin pasar mucho tiempo sin ver las ganancias de las ventas, además de anadir el estado de "entregado"a las personas que hicieron su liquidado con algún botón que surgiera a la hora de ver los abonos si este si fue liquidado darle como entregado y que cambie el estado, porque también se puede ver los abonos de pagos cancelados pero obviamente ahí no saldría porque no hay nada que ver
