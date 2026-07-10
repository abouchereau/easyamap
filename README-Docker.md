# Docker

Cette base permet de lancer Easyamap avec :

- PHP 7.4
- Apache
- MariaDB 11

## Démarrage rapide

```bash
docker compose up --build
```

L'application est ensuite disponible sur :

```text
http://localhost:8080
```

## Base de données

Le conteneur MariaDB initialise automatiquement la base `easyamap` avec le script :

```text
data/amap_init.sql
```

Si tu veux repartir de zéro :

```bash
docker compose down -v
docker compose up --build
```

## Configuration

Le projet utilise encore `config/url2env.php` pour faire correspondre le nom d'hôte à l'environnement applicatif.

Par défaut, la première version Docker monte :

```php
'localhost' => ['local', true],
```

Si tu testes via un autre nom de domaine ou un reverse proxy, ajuste ce fichier en conséquence.

## Variables importantes

- `DATABASE_URL` pointe vers le service `db`
- `APP_SECRET` doit être changé pour un vrai déploiement
- `MAILER_URL` est laissé en mode neutre par défaut

## Notes de production

Cette première version est pensée pour tester facilement l'application et la démarrer sur un serveur simple.
Pour une vraie mise en production, on pourra ensuite :

- ajouter un reverse proxy HTTPS
- externaliser les variables sensibles
- séparer plus proprement les migrations et l'initialisation de la base
- prévoir une image un peu plus durcie
