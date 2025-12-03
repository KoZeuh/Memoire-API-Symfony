Voici un projet fraichement initié, voici la liste des commandes entrée dans le terminal pour le créer :

```bash
# Créer un nouveau répertoire pour le projet
symfony new my_project
cd my_project

# Vérifier les informations sur le projet Symfony
php bin/console about

# Installer les dépendances nécessaires via Composer
composer require symfony/maker-bundle --dev
composer require symfony/orm-pack

# Il est important de configurer la base de données dans le fichier .env
# Exemple de configuration pour une base de données MySQL
# DATABASE_URL="mysql://db_user:db_password@db_host:db_port/db_name"

# Créer la base de données
php bin/console doctrine:database:create

# Lancer le serveur de développement Symfony
symfony server:start
```