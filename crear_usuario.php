<?php
// crear_usuario.php
use App\Entity\User;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;

require __DIR__.'/vendor/autoload.php';

// --- ESTO ES LO QUE FALTABA: Cargar el archivo .env ---
if (file_exists(__DIR__.'/.env')) {
    (new Dotenv())->bootEnv(__DIR__.'/.env');
}

Debug::enable();

$kernel = new App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

// Limpiar tabla por seguridad
$entityManager->getConnection()->executeStatement('DELETE FROM user');

$user = new User();
$user->setEmail('admin@institucion.gob.do');
$user->setRoles(['ROLE_ADMIN']);
// Hash para la contraseña: admin123
$user->setPassword('$2y$13$V9P36uT6NAti7x3w.jO6Su7lMv.zR/rA9oE97R2M0A9V.uO9/oWPi');

$entityManager->persist($user);
$entityManager->flush();

echo "--- ¡USUARIO CREADO CON ÉXITO! ---\n";
echo "Email: admin@institucion.gob.do\n";
echo "Pass: admin123\n";
