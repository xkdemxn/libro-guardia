<?php

/*
|--------------------------------------------------------------------------
| Países, provincias y ciudades para los desplegables del perfil
|--------------------------------------------------------------------------
|
| Los nombres de los países coinciden con las entidades de la tabla prefijos_pais, así la bandera
| y el continente salen de ahí. Ecuador va por provincia y cantón (los 221 cantones); el resto de
| países trae sus ciudades principales. Islas Galápagos no aparece como país: es la provincia de
| Galápagos dentro de Ecuador.
|
*/

return [

    'ecuador' => [
        'Azuay' => ['Cuenca', 'Camilo Ponce Enríquez', 'Chordeleg', 'El Pan', 'Girón', 'Guachapala', 'Gualaceo', 'Nabón', 'Oña', 'Paute', 'Pucará', 'San Fernando', 'Santa Isabel', 'Sevilla de Oro', 'Sígsig'],
        'Bolívar' => ['Guaranda', 'Caluma', 'Chillanes', 'Chimbo', 'Echeandía', 'Las Naves', 'San Miguel'],
        'Cañar' => ['Azogues', 'Biblián', 'Cañar', 'Déleg', 'El Tambo', 'La Troncal', 'Suscal'],
        'Carchi' => ['Tulcán', 'Bolívar', 'Espejo', 'Mira', 'Montúfar', 'San Pedro de Huaca'],
        'Chimborazo' => ['Riobamba', 'Alausí', 'Chambo', 'Chunchi', 'Colta', 'Cumandá', 'Guamote', 'Guano', 'Pallatanga', 'Penipe'],
        'Cotopaxi' => ['Latacunga', 'La Maná', 'Pangua', 'Pujilí', 'Salcedo', 'Saquisilí', 'Sigchos'],
        'El Oro' => ['Machala', 'Arenillas', 'Atahualpa', 'Balsas', 'Chilla', 'El Guabo', 'Huaquillas', 'Las Lajas', 'Marcabelí', 'Pasaje', 'Piñas', 'Portovelo', 'Santa Rosa', 'Zaruma'],
        'Esmeraldas' => ['Esmeraldas', 'Atacames', 'Eloy Alfaro', 'Muisne', 'Quinindé', 'Rioverde', 'San Lorenzo'],
        'Galápagos' => ['Puerto Baquerizo Moreno', 'Puerto Ayora', 'Puerto Villamil'],
        'Guayas' => ['Guayaquil', 'Alfredo Baquerizo Moreno', 'Balao', 'Balzar', 'Colimes', 'Coronel Marcelino Maridueña', 'Daule', 'Durán', 'El Empalme', 'El Triunfo', 'General Antonio Elizalde', 'Isidro Ayora', 'Lomas de Sargentillo', 'Milagro', 'Naranjal', 'Naranjito', 'Nobol', 'Palestina', 'Pedro Carbo', 'Playas', 'Salitre', 'Samborondón', 'Santa Lucía', 'Simón Bolívar', 'Yaguachi'],
        'Imbabura' => ['Ibarra', 'Antonio Ante', 'Cotacachi', 'Otavalo', 'Pimampiro', 'Urcuquí'],
        'Loja' => ['Loja', 'Calvas', 'Catamayo', 'Celica', 'Chaguarpamba', 'Espíndola', 'Gonzanamá', 'Macará', 'Olmedo', 'Paltas', 'Pindal', 'Puyango', 'Quilanga', 'Saraguro', 'Sozoranga', 'Zapotillo'],
        'Los Ríos' => ['Babahoyo', 'Baba', 'Buena Fe', 'Mocache', 'Montalvo', 'Palenque', 'Puebloviejo', 'Quevedo', 'Quinsaloma', 'Urdaneta', 'Valencia', 'Ventanas', 'Vinces'],
        'Manabí' => ['Portoviejo', 'Bolívar', 'Chone', 'El Carmen', 'Flavio Alfaro', 'Jama', 'Jaramijó', 'Jipijapa', 'Junín', 'Manta', 'Montecristi', 'Olmedo', 'Paján', 'Pedernales', 'Pichincha', 'Puerto López', 'Rocafuerte', 'San Vicente', 'Santa Ana', 'Sucre', 'Tosagua', '24 de Mayo'],
        'Morona Santiago' => ['Macas', 'Gualaquiza', 'Huamboya', 'Limón Indanza', 'Logroño', 'Pablo Sexto', 'Palora', 'San Juan Bosco', 'Santiago', 'Sucúa', 'Taisha', 'Tiwintza'],
        'Napo' => ['Tena', 'Archidona', 'Carlos Julio Arosemena Tola', 'El Chaco', 'Quijos'],
        'Orellana' => ['Francisco de Orellana (El Coca)', 'Aguarico', 'La Joya de los Sachas', 'Loreto'],
        'Pastaza' => ['Puyo', 'Arajuno', 'Mera', 'Santa Clara'],
        'Pichincha' => ['Quito', 'Cayambe', 'Mejía', 'Pedro Moncayo', 'Pedro Vicente Maldonado', 'Puerto Quito', 'Rumiñahui', 'San Miguel de los Bancos'],
        'Santa Elena' => ['Santa Elena', 'La Libertad', 'Salinas'],
        'Santo Domingo' => ['Santo Domingo', 'La Concordia'],
        'Sucumbíos' => ['Nueva Loja (Lago Agrio)', 'Cascales', 'Cuyabeno', 'Gonzalo Pizarro', 'Putumayo', 'Shushufindi', 'Sucumbíos'],
        'Tungurahua' => ['Ambato', 'Baños de Agua Santa', 'Cevallos', 'Mocha', 'Patate', 'Pelileo', 'Píllaro', 'Quero', 'Tisaleo'],
        'Zamora Chinchipe' => ['Zamora', 'Centinela del Cóndor', 'Chinchipe', 'El Pangui', 'Nangaritza', 'Palanda', 'Paquisha', 'Yacuambi', 'Yantzaza'],
    ],

    'paises' => [
        'Alaska' => ['Anchorage', 'Fairbanks', 'Juneau', 'Kodiak', 'Ketchikan', 'Sitka', 'Wasilla'],
        'Alemania' => ['Berlín', 'Hamburgo', 'Múnich', 'Colonia', 'Fráncfort', 'Stuttgart', 'Düsseldorf', 'Dortmund', 'Leipzig', 'Dresde', 'Hannover', 'Núremberg', 'Bremen'],
        'Argentina' => ['Buenos Aires', 'Córdoba', 'Rosario', 'Mendoza', 'La Plata', 'San Miguel de Tucumán', 'Mar del Plata', 'Salta', 'Santa Fe', 'San Juan', 'Neuquén', 'Bahía Blanca', 'Ushuaia'],
        'Australia' => ['Sídney', 'Melbourne', 'Brisbane', 'Perth', 'Adelaida', 'Canberra', 'Hobart', 'Darwin', 'Gold Coast'],
        'Austria' => ['Viena', 'Graz', 'Linz', 'Salzburgo', 'Innsbruck', 'Klagenfurt'],
        'Bélgica' => ['Bruselas', 'Amberes', 'Gante', 'Charleroi', 'Lieja', 'Brujas', 'Namur', 'Lovaina'],
        'Bolivia' => ['La Paz', 'Santa Cruz de la Sierra', 'Cochabamba', 'Sucre', 'El Alto', 'Oruro', 'Potosí', 'Tarija', 'Trinidad', 'Cobija'],
        'Brasil' => ['São Paulo', 'Río de Janeiro', 'Brasilia', 'Salvador', 'Fortaleza', 'Belo Horizonte', 'Manaos', 'Curitiba', 'Recife', 'Porto Alegre', 'Belém', 'Goiânia', 'Florianópolis'],
        'Canadá' => ['Toronto', 'Montreal', 'Vancouver', 'Calgary', 'Edmonton', 'Ottawa', 'Winnipeg', 'Quebec', 'Halifax', 'Victoria', 'Regina', 'St. John\'s'],
        'Chile' => ['Santiago', 'Valparaíso', 'Viña del Mar', 'Concepción', 'Antofagasta', 'La Serena', 'Temuco', 'Rancagua', 'Talca', 'Arica', 'Iquique', 'Puerto Montt', 'Punta Arenas'],
        'China' => ['Pekín', 'Shanghái', 'Cantón', 'Shenzhen', 'Chengdú', 'Chongqing', 'Tianjin', 'Wuhan', 'Xi\'an', 'Hangzhou', 'Nankín'],
        'Colombia' => ['Bogotá', 'Medellín', 'Cali', 'Barranquilla', 'Cartagena', 'Bucaramanga', 'Pereira', 'Santa Marta', 'Manizales', 'Cúcuta', 'Ibagué', 'Pasto', 'Popayán', 'Villavicencio'],
        'Corea del Sur' => ['Seúl', 'Busan', 'Incheon', 'Daegu', 'Daejeon', 'Gwangju', 'Ulsan', 'Suwon'],
        'Costa Rica' => ['San José', 'Alajuela', 'Cartago', 'Heredia', 'Liberia', 'Puntarenas', 'Limón'],
        'Cuba' => ['La Habana', 'Santiago de Cuba', 'Camagüey', 'Holguín', 'Santa Clara', 'Guantánamo', 'Matanzas', 'Cienfuegos', 'Pinar del Río'],
        'Dinamarca' => ['Copenhague', 'Aarhus', 'Odense', 'Aalborg', 'Esbjerg', 'Roskilde'],
        'Ecuador' => [],
        'Egipto' => ['El Cairo', 'Alejandría', 'Guiza', 'Port Said', 'Suez', 'Luxor', 'Asuán'],
        'El Salvador' => ['San Salvador', 'Santa Ana', 'San Miguel', 'Soyapango', 'Santa Tecla', 'La Libertad'],
        'Escocia' => ['Edimburgo', 'Glasgow', 'Aberdeen', 'Dundee', 'Inverness', 'Stirling'],
        'España' => ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Zaragoza', 'Málaga', 'Murcia', 'Bilbao', 'Alicante', 'Córdoba', 'Valladolid', 'Vigo', 'Granada', 'Oviedo', 'Pamplona', 'Santander'],
        'Estados Unidos' => ['Nueva York', 'Los Ángeles', 'Chicago', 'Houston', 'Phoenix', 'Filadelfia', 'San Antonio', 'San Diego', 'Dallas', 'Miami', 'Atlanta', 'Seattle', 'Denver', 'Boston', 'Washington D. C.', 'Las Vegas', 'Orlando', 'San Francisco'],
        'Filipinas' => ['Manila', 'Quezón', 'Davao', 'Cebú', 'Zamboanga', 'Bacolod', 'Iloílo', 'Baguio'],
        'Finlandia' => ['Helsinki', 'Espoo', 'Tampere', 'Vantaa', 'Turku', 'Oulu', 'Rovaniemi'],
        'Francia' => ['París', 'Marsella', 'Lyon', 'Toulouse', 'Niza', 'Nantes', 'Estrasburgo', 'Montpellier', 'Burdeos', 'Lille', 'Rennes'],
        'Gales' => ['Cardiff', 'Swansea', 'Newport', 'Wrexham', 'Bangor', 'Aberystwyth'],
        'Grecia' => ['Atenas', 'Tesalónica', 'Patras', 'Heraclión', 'Larisa', 'Volos'],
        'Guatemala' => ['Ciudad de Guatemala', 'Quetzaltenango', 'Escuintla', 'Mixco', 'Villa Nueva', 'Antigua Guatemala', 'Cobán', 'Puerto Barrios'],
        'Hawái' => ['Honolulu', 'Hilo', 'Kailua', 'Kahului', 'Lihue', 'Kona'],
        'Honduras' => ['Tegucigalpa', 'San Pedro Sula', 'La Ceiba', 'Choloma', 'El Progreso', 'Comayagua', 'Choluteca'],
        'Hungría' => ['Budapest', 'Debrecen', 'Szeged', 'Miskolc', 'Pécs', 'Győr'],
        'India' => ['Nueva Delhi', 'Bombay', 'Bangalore', 'Calcuta', 'Chennai', 'Hyderabad', 'Ahmedabad', 'Pune', 'Jaipur'],
        'Indonesia' => ['Yakarta', 'Surabaya', 'Bandung', 'Medan', 'Semarang', 'Makassar', 'Denpasar', 'Yogyakarta'],
        'Inglaterra' => ['Londres', 'Birmingham', 'Mánchester', 'Liverpool', 'Leeds', 'Bristol', 'Sheffield', 'Newcastle', 'Nottingham', 'Oxford', 'Cambridge'],
        'Irlanda' => ['Dublín', 'Cork', 'Limerick', 'Galway', 'Waterford', 'Kilkenny'],
        'Irlanda del Norte' => ['Belfast', 'Derry', 'Lisburn', 'Newry', 'Armagh'],
        'Islas Baleares' => ['Palma de Mallorca', 'Ibiza', 'Mahón', 'Manacor', 'Ciudadela'],
        'Islas Canarias' => ['Las Palmas de Gran Canaria', 'Santa Cruz de Tenerife', 'San Cristóbal de La Laguna', 'Arrecife', 'Puerto del Rosario', 'Santa Cruz de La Palma'],
        'Israel' => ['Jerusalén', 'Tel Aviv', 'Haifa', 'Rishon LeZion', 'Beerseba', 'Eilat'],
        'Italia' => ['Roma', 'Milán', 'Nápoles', 'Turín', 'Palermo', 'Génova', 'Bolonia', 'Florencia', 'Venecia', 'Bari', 'Verona'],
        'Japón' => ['Tokio', 'Osaka', 'Yokohama', 'Nagoya', 'Sapporo', 'Kobe', 'Kioto', 'Fukuoka', 'Hiroshima', 'Sendai'],
        'Marruecos' => ['Rabat', 'Casablanca', 'Marrakech', 'Fez', 'Tánger', 'Agadir', 'Mequinez'],
        'México' => ['Ciudad de México', 'Guadalajara', 'Monterrey', 'Puebla', 'Tijuana', 'León', 'Querétaro', 'Mérida', 'Cancún', 'Toluca', 'San Luis Potosí', 'Chihuahua', 'Veracruz', 'Oaxaca'],
        'Nicaragua' => ['Managua', 'León', 'Masaya', 'Granada', 'Matagalpa', 'Chinandega', 'Estelí'],
        'Noruega' => ['Oslo', 'Bergen', 'Trondheim', 'Stavanger', 'Tromsø', 'Kristiansand'],
        'Nueva Zelanda' => ['Auckland', 'Wellington', 'Christchurch', 'Hamilton', 'Dunedin', 'Tauranga'],
        'Países Bajos' => ['Ámsterdam', 'Róterdam', 'La Haya', 'Utrecht', 'Eindhoven', 'Groninga', 'Maastricht'],
        'Panamá' => ['Ciudad de Panamá', 'San Miguelito', 'Colón', 'David', 'La Chorrera', 'Santiago de Veraguas'],
        'Paraguay' => ['Asunción', 'Ciudad del Este', 'San Lorenzo', 'Luque', 'Capiatá', 'Encarnación', 'Pedro Juan Caballero'],
        'Perú' => ['Lima', 'Arequipa', 'Trujillo', 'Chiclayo', 'Piura', 'Cusco', 'Iquitos', 'Huancayo', 'Tacna', 'Puno', 'Cajamarca', 'Tumbes'],
        'Polonia' => ['Varsovia', 'Cracovia', 'Łódź', 'Breslavia', 'Poznań', 'Gdansk', 'Szczecin', 'Lublin'],
        'Portugal' => ['Lisboa', 'Oporto', 'Braga', 'Coímbra', 'Faro', 'Funchal', 'Ponta Delgada'],
        'Puerto Rico' => ['San Juan', 'Bayamón', 'Carolina', 'Ponce', 'Caguas', 'Mayagüez', 'Arecibo'],
        'República Checa' => ['Praga', 'Brno', 'Ostrava', 'Pilsen', 'Olomouc', 'Liberec'],
        'República Dominicana' => ['Santo Domingo', 'Santiago de los Caballeros', 'La Romana', 'San Pedro de Macorís', 'Puerto Plata', 'San Francisco de Macorís', 'Punta Cana'],
        'Rumania' => ['Bucarest', 'Cluj-Napoca', 'Timișoara', 'Iași', 'Constanza', 'Brașov', 'Craiova'],
        'Rusia asiática' => ['Novosibirsk', 'Ekaterimburgo', 'Omsk', 'Krasnoyarsk', 'Irkutsk', 'Vladivostok', 'Jabárovsk', 'Tomsk'],
        'Rusia europea' => ['Moscú', 'San Petersburgo', 'Kazán', 'Nizhni Nóvgorod', 'Samara', 'Rostov del Don', 'Volgogrado', 'Kaliningrado', 'Múrmansk'],
        'Sudáfrica' => ['Johannesburgo', 'Ciudad del Cabo', 'Durban', 'Pretoria', 'Port Elizabeth', 'Bloemfontein'],
        'Suecia' => ['Estocolmo', 'Gotemburgo', 'Malmö', 'Upsala', 'Västerås', 'Örebro', 'Linköping', 'Umeå', 'Kiruna'],
        'Suiza' => ['Zúrich', 'Ginebra', 'Basilea', 'Berna', 'Lausana', 'Lucerna', 'Lugano'],
        'Tailandia' => ['Bangkok', 'Chiang Mai', 'Phuket', 'Pattaya', 'Nakhon Ratchasima', 'Hat Yai'],
        'Turquía' => ['Estambul', 'Ankara', 'Esmirna', 'Bursa', 'Antalya', 'Adana', 'Konya', 'Trebisonda'],
        'Ucrania' => ['Kiev', 'Járkov', 'Odesa', 'Dnipró', 'Leópolis', 'Zaporiyia', 'Vínnytsia'],
        'Uruguay' => ['Montevideo', 'Salto', 'Paysandú', 'Las Piedras', 'Rivera', 'Maldonado', 'Punta del Este', 'Colonia del Sacramento', 'Tacuarembó'],
        'Venezuela' => ['Caracas', 'Maracaibo', 'Valencia', 'Barquisimeto', 'Maracay', 'Ciudad Guayana', 'Barcelona', 'Mérida', 'San Cristóbal', 'Puerto La Cruz', 'Cumaná'],
    ],

    /*
     * Zonas ITU y CQ de Ecuador continental y de Galápagos (las mismas para todo el país).
     */
    'zonas_ecuador' => ['itu' => 12, 'cq' => 10],

];
