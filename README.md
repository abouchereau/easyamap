# Easyamap
Logiciel de commandes pour les AMAP

![Easyamap Logo](https://www.easyamap.fr/images/logo-easy-amap-160.png)

## Présentation
Easyamap est un logiciel en ligne de gestion des commandes pour les AMAP, basé sur le framework Symfony (version 4.4). 
Site de présentation : https://www.easyamap.fr

Pour mettre en place l'outil dans votre AMAP, vous pouvez me contacter à cette adresse : anthony [arobase] easyamap [point] fr.
Nous pourrons mettre en place une solution hébergée, vous n'aurez rien besoin d'installer.

Si vous souhaitez installer vous-mêmes le logiciel, merci de suivre les étapes de l'installation décrite ci-dessous.
**Note** : _aucun support n'est garanti_ pour l'installation du logiciel.

## Installation
_L'installation a été testée sur un serveur dédié avec Debian 10, Apache 2.4, PHP 7.3, MariaDb 10.3._

### Prérequis
Avoir installé :
* Apache 
* PHP 
* MySQL ou MariaDb
* Composer
* Git

### Récupération du code
```bash
cd /var/www/
git clone https://github.com/abouchereau/easyamap.git
cd easyamap
composer install
```

### Virtual Host
```
<VirtualHost *:80>
      DocumentRoot /var/www/easyamap/public
      ServerName www.mon-domaine.com

      <Directory "/var/www/easyamap/public">
              Options FollowSymLinks MultiViews
              AllowOverride All
              Require all granted
        <IfModule mod_rewrite.c>
            Options -MultiViews
                  RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^(.*)$ index.php [QSA,L]
        </IfModule>
      </Directory>
</VirtualHost>
```
_Note :_ Il est recommandé d'utiliser le HTTPS. Ajuster si besoin votre Virtual Host en ajoutant votre certificat, le port 443 etc...

## Création de la base de données
```bash
mysql -u root -p
```
```sql
create database easyamap;
CREATE USER 'easyamap'@'localhost' IDENTIFIED WITH mysql_native_password BY 'MonMotDePasse';
GRANT ALL ON easyamap.* to 'easyamap'@'localhost';
```
Modifier le fichier config/packages/prod/doctrine.yaml

```yml
doctrine:
    dbal:
        url: 'mysql://easyamap:MonMotDePasse@127.0.0.1:3306/easyamap'
```





