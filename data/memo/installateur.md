# Ajouter un installateur Symfony

> Je voudrais proposer un installeur pour mon appli, à la manière de wordpress. Il vérifierait que l'environnement est bon avec les bonnes version et permettrait à l'utilisateur de mettre ses données de connexion à la BDD, ainsi que le SMTP.
> Est-ce possible sur une application Symfony ?
> Le problème c'est que la racine web est dans public, donc si l'utilisateur dézippe l'archive de l'application sur son hébergement mutualisé, il verra la racine de l'application et non pas la racine de public/ . pour que l'installeur reste simple, je ne voudrais que l'utilisateur soit obligé de gérer la configuration par défaut de son hébergement.
> Comment faire ?

<details><summary>2 previous messages</summary>

> Je vais d’abord regarder la structure du projet pour voir comment il est déployé aujourd’hui, puis je te dirai la voie la plus simple pour un installateur Symfony sur mutualisé et ce qu’il faut éviter.
>
> Je confirme le point de départ actuel du projet. Ensuite je vais te répondre en distinguant ce qui est faisable sur Symfony, ce qui est pénible sur mutualisé, et le contournement le plus robuste pour garder l’installation simple.
</details>

Oui, c’est possible sur Symfony.

Le point clé, c’est que l’installateur ne peut pas “corriger” à lui seul un mauvais `DocumentRoot`. Le serveur web décide ce qui est exposé. Donc si l’utilisateur dézippe tout dans un dossier servi tel quel, le vrai sujet n’est pas Symfony, c’est la façon dont tu emballes et exposes l’application.

Dans ton projet actuel, on voit déjà les contraintes:
- le front controller est dans [public/index.php](./public/index.php),
- la config est chargée très tôt dans [config/bootstrap.php](./config/bootstrap.php),
- et ton README demande encore de modifier à la main [README.md](./README.md), `config/url2env.php`, `config/packages/local/doctrine.yaml` et `.env`.

## La bonne approche

### 1. Faire un vrai “mode installation”
Tu ajoutes une route ou un front controller d’installation, par exemple:
- `/install`
- vérification de PHP et des extensions
- vérification de la version Symfony / Composer si nécessaire
- test de connexion BDD
- test SMTP
- création des tables / migrations
- écriture de la config locale
- création d’un `install.lock` pour désactiver l’install ensuite

### 2. Ne pas demander à l’utilisateur d’éditer les fichiers à la main
L’installateur doit générer les valeurs locales dans un fichier écrit automatiquement, par exemple:
- `.env.local.php`
- ou `config/packages/local/*.yaml`
- ou un fichier `config/local.php` à toi

Symfony supporte déjà très bien le principe de config locale générée.

### 3. Séparer “code applicatif” et “racine web”
C’est la partie importante pour ton problème de mutualisé.

Tu as 3 options:

#### Option A, la plus propre
Le serveur pointe vers `public/`.

C’est la structure Symfony normale, la plus saine.
Mais elle suppose que l’utilisateur peut configurer le vhost ou le dossier racine du domaine.

#### Option B, la plus simple pour l’utilisateur
Tu fournis un package “mutualisé” spécial, différent du projet source:
- à la racine du zip: seulement `index.php`, `.htaccess`, éventuellement les assets publics
- le reste de l’application dans un dossier non exposé directement, par exemple `app/`

Le `index.php` racine sert de bootstrap et renvoie tout vers Symfony.
C’est souvent la meilleure option si tu veux un installateur “à la WordPress”.

#### Option C, possible mais plus fragile
Tu laisses tout dans le même répertoire mais tu ajoutes des règles `.htaccess` pour:
- interdire l’accès aux fichiers sensibles
- rediriger toutes les requêtes vers l’installateur ou le front controller

Ça marche sur beaucoup de mutualisés Apache, mais ça dépend fortement du support de `.htaccess` et des règles de rewrite.

## Ce que je te recommande
Pour ton cas, je ferais ceci:

1. garder le projet Symfony “normal” en interne;
2. générer un **build de distribution mutualisée**;
3. dans ce build, mettre un `index.php` racine et un `.htaccess`;
4. faire démarrer l’app sur une page `/install` tant que l’installation n’est pas faite;
5. écrire les identifiants BDD et SMTP dans un fichier local généré;
6. lancer les migrations;
7. créer un verrou `install.lock`.

## Point d’attention dans ton code actuel
Tu as déjà une logique spécifique dans [public/index.php](./public/index.php):

- chargement de `config/url2env.php`
- chargement de `config/maintenance.php`
- sélection de l’environnement avant de créer le kernel

Ça veut dire que l’installateur devra probablement:
- soit remplacer cette logique par une config plus simple et locale,
- soit fonctionner avant tout le reste, avec un bootstrap minimal.

Autre détail: ton README parle de `MAILER_URL`, mais ton fichier [config/packages/mailer.yaml](./config/packages/mailer.yaml) lit `MAILER_DSN`.
Si tu mets en place l’installateur, il faudra écrire la bonne variable pour éviter une incohérence.

## Réponse courte à ta question
Oui, c’est faisable.
Mais pour que ce soit simple sur mutualisé, il faut préparer une **distribution dédiée à l’installation web**, pas juste zipper le projet Symfony brut.

Si tu veux, je peux te proposer juste après:
1. une architecture concrète d’installateur Symfony pour ton projet actuel,
2. ou un plan de refactor très précis pour passer de `config/url2env.php` + `.env` manuel à une installation automatisée.

> Si on part sur l'option B, comment faire pour packager l'application avec l'index.php à la racine et le reste dans app ?
> Je voudrais aussi que les vendor soient inclus pour que l'utilisateur n'ait pas à utiliser composer.
>
> Il existe des outils pour réaliser cela ?

Oui, et ce n’est pas un problème Symfony en soi, c’est surtout un problème de **packaging de distribution**.

Pour ton cas, il faut penser en 2 couches:
- un **build d’installation** pour l’utilisateur final
- un **projet source** classique pour toi

## Ce que je te conseille

Tu fabriques une archive “mutualisé-friendly” avec une structure du genre:

```text
/
  index.php
  .htaccess
  install.php ou install/
  app/
    bin/
    config/
    public/
    src/
    templates/
    var/
    vendor/
```

L’idée:
- l’utilisateur dézippe à la racine de son hébergement
- le serveur pointe sur cette racine
- `index.php` à la racine redirige vers l’application dans `app/`
- l’installateur écrit la config locale, teste la BDD, le SMTP, etc.
- une fois fini, tu crées un `install.lock`

## Comment le faire concrètement

### 1. Faire un script de build
Tu n’as pas besoin d’un outil magique Symfony. Le plus propre est un script de packaging qui:

1. lance `composer install --no-dev --optimize-autoloader`
2. copie le code dans un dossier de distribution
3. met `vendor/` dedans
4. crée le `index.php` racine
5. ajoute `.htaccess`
6. zippe le tout

Ça peut être fait avec:
- un script PowerShell
- un script bash
- `Phing`
- `Robo`
- un `Makefile`

Pour un projet Symfony web, je privilégierais:
- **PowerShell / bash** si tu veux simple
- **Robo** ou **Phing** si tu veux quelque chose de plus structuré

## 2. Garder `vendor/` dans l’archive
Oui, c’est totalement normal si tu veux éviter Composer chez le client.

Le build se fait chez toi ou en CI:
- `composer install` pendant la génération du package
- puis tu distribues l’archive finale avec `vendor/` déjà inclus

L’utilisateur final n’a plus besoin de Composer.

## 3. Adapter le front controller
Dans ton projet actuel, [public/index.php](./public/index.php) est le point d’entrée Symfony normal.
Pour une distribution mutualisée, tu peux créer un `index.php` à la racine qui fait simplement:

```php
<?php
require __DIR__.'/app/public/index.php';
```

Ça permet à l’utilisateur d’ouvrir l’appli depuis la racine du dossier dézippé, sans config serveur particulière.

## 4. Protéger `app/`
Comme `app/` sera sous la racine web, il faut empêcher l’accès direct:
- avec un `.htaccess` dans `app/`
- et éventuellement un autre dans `app/public/` selon la structure choisie

Sinon l’utilisateur pourrait voir des fichiers qu’il ne devrait pas.

## 5. Prévoir un vrai installateur web
Ton installateur devra ensuite:
- tester la version PHP et les extensions
- vérifier les droits d’écriture
- demander les infos BDD
- demander les infos SMTP
- écrire la config locale
- exécuter les migrations / l’initialisation de la base
- verrouiller l’installation

## Outils existants

### Oui, mais avec nuance
Il existe des outils pour **automatiser le packaging**, mais pas vraiment un outil Symfony “officiel” qui transforme directement ton app en archive installable façon WordPress.

Les options utiles:
- **Phing**: bien pour orchestrer un build
- **Robo**: bon pour écrire des tâches de packaging en PHP
- **Box**: utile surtout pour des PHAR, donc pas mon premier choix pour une appli web Symfony
- **Deployer**: très bien pour déployer, moins pour fabriquer l’archive finale
- **Composer scripts**: parfois suffisant si le besoin reste simple

Pour ton besoin, je pense que le plus réaliste est:
- **Composer + script de build maison**
- éventuellement orchestré par **Robo**

## Point important dans ton projet actuel
Il y a un détail à corriger avant d’aller plus loin: ton bootstrap dépend de `symfony/dotenv`, mais dans [composer.json](./composer.json), il est en `require-dev`.
Si tu veux une installation web sans Composer côté client, il faudra soit:
- le mettre en `require`
- soit ne plus dépendre de Dotenv au runtime

Sinon ton bootstrap risque de casser dans l’archive distribuée.

Autre point: ton [public/index.php](./public/index.php) charge aussi `config/url2env.php` et `config/maintenance.php`.
Dans une install “simple”, il faudra remplacer ça par une config générée automatiquement par l’installateur.

## En pratique, la meilleure stratégie
Je ferais:
1. un projet Symfony normal pour le dev
2. un script de build qui fabrique une archive “client”
3. un `index.php` racine pour le mutualisé
4. un installateur web qui écrit la config locale
5. un verrou `install.lock` après fin d’installation

Si tu veux, je peux te proposer juste après:
1. une **arborescence cible exacte** pour ton projet,
2. ou un **script de build concret** pour générer l’archive distribuable.

> Peux-tu me fournir une première version d'un script bash pour packager qui va donc :
> - copier l'ensemble du projet dans un nouveau dossier
> - tout déplacer dans app/ <

> - créer le fichier index.php à la racine
> - ajouter le .htaccess pour limiter les droits sur app/
> => il faudrait que l'application puisse fonctionne avec cette nouvelle arborescence
> - puis zipper
>
> On verra pour l'installeur dans un 2e temps

<details><summary>9 previous messages</summary>

> Je vais regarder comment le projet sert ses assets et ce qui dépend vraiment de `public/`, pour te proposer un packaging qui marche sans casser l’application. Ensuite je te fournis un script bash de première version, avec l’arborescence cible.
>
> J’ai identifié le vrai point de vigilance: les templates utilisent `asset('...')`, donc si on change l’arborescence il faut aussi préserver l’accès aux fichiers publics. Je vais regarder le `.htaccess` actuel et, ensuite, je te prépare un script de packaging qui garde l’app fonctionnelle sans Composer côté client.
>
> Je cherche maintenant les chemins codés en dur vers `public/` ou la racine du projet, pour vérifier s’il faut aussi prévoir une petite adaptation applicative dans le package ou si le script suffit.
>
> Je suis en train de choisir le compromis le plus sûr: garder l’application telle quelle et faire porter toute l’adaptation par le package, sans toucher au code métier. Je vérifie juste la gestion actuelle de `dist/` pour éviter que le script génère des artefacts suivis par Git.
>
> Je vais ajouter un script `scripts/package.sh` et ignorer le dossier `dist/` pour que les archives générées ne polluent pas le dépôt. Le script va fabriquer l’arborescence finale, générer `index.php` et le `.htaccess`, puis zipper le tout.
>
> <details><summary>Created a file, edited a file</summary>
>
> - Wrote `./.gitignore` (+2 -1)
> - Wrote `./scripts/package.sh` (+100 -0)
>
> </details>
>
> Le script est en place. Je vais faire une vérification de syntaxe minimale pour éviter de te livrer un bash cassé, puis je te résume exactement ce qu’il génère et ses limites actuelles.
>
> Je fais un petit ajustement de finition sur le script: un rappel d’usage clair et l’exclusion du dossier `scripts/` du package final, pour que l’archive distribuée reste propre.
>
> <details><summary>Edited a file</summary>
>
> - Wrote `./scripts/package.sh` (+15 -0)
>
> </details>
</details>

J’ai ajouté une première version du packager dans [scripts/package.sh](./scripts/package.sh) et j’ai fait en sorte que `dist/` soit ignoré dans [/.gitignore](./.gitignore).

Ce que fait le script:
- copie tout le projet dans `dist/<nom_du_package>/app`
- nettoie `var/cache` et `var/log` dans la copie
- génère un `index.php` à la racine du package qui charge `app/public/index.php`
- génère un `.htaccess` à la racine pour:
  - protéger l’accès direct à `app/`
  - servir les assets depuis `app/public/`
  - rediriger le reste vers le front controller Symfony
- crée ensuite le zip final dans `dist/<nom_du_package>.zip`

Utilisation:
```bash
bash scripts/package.sh easyamap
```

Ça gardera bien `vendor/` dans le package, donc l’utilisateur final n’aura pas besoin de Composer sur son hébergement.

Je n’ai pas pu exécuter une vraie vérification bash ici parce que l’environnement n’a pas de `bash/WSL` disponible, mais le script est prêt pour une exécution sur une machine Linux, WSL, ou Git Bash avec `rsync` et `zip`.

Si tu veux, je peux maintenant faire une V2 plus propre qui:
1. exclut aussi les fichiers de dev inutiles du package final,
2. lance `composer install --no-dev` automatiquement avant le zip,
3. ou prépare déjà la base pour l’installateur web.

> Ok, je voudrais maintenant créer la base de l'installeur.
> Comment t'y prendrais-tu ?

Je partirais sur un installateur en 2 couches, avec une règle simple: **l’installateur ne doit pas dépendre du reste de l’application tant que la config n’existe pas**.

## L’idée générale

Je ferais:

- un **front controller d’installation** séparé, par exemple `install.php` à la racine du package
- un **front controller normal** pour l’application, `index.php`
- un **état d’installation** stocké dans un fichier, par exemple `app/var/install.lock`
- une **config locale générée** automatiquement par l’installateur
- une **page de fin** qui verrouille l’accès à l’installateur

Comme ça, l’utilisateur dézippe, va sur le site, et tombe sur l’installateur si l’appli n’est pas encore configurée.

## Ce que je ferais en premier

### 1. Ajouter un bootstrap minimal
Avant de charger Symfony “normalement”, le `index.php` racine vérifierait:

- si `install.lock` existe
- si la config locale est présente
- sinon, redirection vers l’installateur

Ça évite de casser l’app si la BDD n’est pas encore configurée.

### 2. Créer une page d’install autonome
Je ferais un `install.php` ou une petite mini-app d’installation qui fait uniquement:

- vérification PHP
- vérification extensions
- vérification écriture disque
- formulaire BDD
- formulaire SMTP
- test de connexion BDD
- test d’envoi SMTP
- écriture des fichiers de config
- initialisation de la base
- création du lock

L’installateur doit être **le plus indépendant possible**.

### 3. Faire écrire la config par l’installateur
Je ne ferais pas demander à l’utilisateur d’éditer `.env` ou YAML à la main.

À la place, l’installateur écrirait par exemple:
- `.env.local.php`
- ou `app/config/local/*.yaml`
- ou un fichier PHP de config dédié

Pour ton projet, vu qu’il y a déjà de la logique maison, je pense qu’un fichier local généré est le plus simple à gérer.

## Architecture que je te recommande

Vu ton packaging avec racine + `app/`, je ferais quelque chose comme:

```text
/
  index.php
  install.php
  .htaccess
  app/
    bin/
    config/
    public/
    src/
    templates/
    var/
    vendor/
```

### Rôle de chaque fichier
- `index.php`: lance l’application normale
- `install.php`: lance l’assistant d’installation
- `.htaccess`: protège `app/` et route le trafic
- `app/`: contient le Symfony complet

## Étapes fonctionnelles de l’installeur

Je le découperais en 4 écrans:

1. **Bienvenue / prérequis**
   - version PHP
   - extensions manquantes
   - droits d’écriture
   - infos d’environnement

2. **Base de données**
   - host
   - port
   - db name
   - user
   - password
   - test de connexion

3. **SMTP**
   - host
   - port
   - user
   - password
   - TLS/SSL
   - test d’envoi

4. **Finalisation**
   - écriture config
   - migration / init SQL
   - création du lock
   - suppression ou désactivation de l’installateur

## Ce que je ferais pour éviter les pièges

### Ne pas lancer Symfony complet trop tôt
L’installateur ne doit pas dépendre:
- de Doctrine branché sur la vraie BDD
- des services métiers
- de config déjà valide

Sinon tu te retrouves avec un installateur qui plante avant même d’afficher le formulaire.

### Garder l’installateur hors du cache applicatif
Je ferais l’installateur en mode très simple:
- PHP pur ou contrôleur ultra léger
- pas de Twig obligatoire au départ
- pas de container Symfony complet

Ensuite, une fois la config écrite, l’app normale peut démarrer.

### Verrouiller l’accès après installation
Je ferais un `install.lock` et je bloquerais tout retour vers l’installateur si ce fichier existe.

## Pour ton projet actuel, le meilleur point d’entrée
Dans ton code actuel, il faudra probablement refondre:
- [public/index.php](./public/index.php)
- [config/bootstrap.php](./config/bootstrap.php)

Car aujourd’hui tu charges déjà des fichiers comme `config/url2env.php` et `config/maintenance.php` trop tôt.
Pour un installateur, il vaut mieux inverser la logique:
- si installé, on lance l’app
- sinon, on lance l’installateur

## Ma recommandation pratique
Je ferais ça en 3 petites étapes:

1. **Créer le mode “install required”**
   - si pas de lock, rediriger vers `install.php`

2. **Créer le formulaire de base**
   - BDD + SMTP + test de connexion

3. **Écrire la config locale et lancer l’initialisation**
   - puis lock

Si tu veux, je peux maintenant te proposer une **arborescence cible très précise** et le **squelette des fichiers à créer** pour démarrer l’installeur proprement.

> ok ça m'a l'air bien.
> Je veux bien voir à quoi ressemblerait l'arborescence
> Ce serait donc quasiment un mini-projet à part ?

Oui, exactement: **c’est quasiment un mini-projet à part**, mais plutôt au sens **“packaging de distribution”** qu’au sens d’une nouvelle application métier.

L’idée la plus saine, c’est:

- ton **repo source** reste un Symfony classique
- ton **package livré à l’utilisateur** est une version “emballée” de ce projet
- l’installation et le démarrage se font depuis la **racine du package**
- l’application Symfony elle-même vit dans `app/`

## Arborescence cible

```text
easyamap-package/
├── .htaccess
├── index.php
├── install.php
├── app/
│   ├── bin/
│   ├── config/
│   ├── public/
│   ├── src/
│   ├── templates/
│   ├── translations/
│   ├── var/
│   ├── vendor/
│   ├── composer.json
│   ├── composer.lock
│   └── ...
└── README.txt
```

## Rôle de chaque bloc

- `index.php`
  - point d’entrée de l’application
  - charge `app/public/index.php` ou un bootstrap équivalent adapté à cette arborescence

- `install.php`
  - assistant d’installation
  - vérifie PHP, extensions, droits, BDD, SMTP
  - écrit la config locale
  - initialise la base
  - crée un lock

- `.htaccess`
  - protège `app/`
  - redirige les requêtes vers le bon front controller
  - laisse passer les assets et fichiers publics

- `app/`
  - contient tout le Symfony “normal”
  - c’est en pratique ton ancien projet déplacé sous ce dossier

## Donc, oui ou non, c’est un mini-projet ?

**Oui pour la distribution. Non pour le code métier.**

Autrement dit:
- tu ne réécris pas l’application
- tu ajoutes une **couche wrapper**
- cette couche gère:
  - l’entrée web racine
  - l’installation
  - la configuration locale
  - la protection des fichiers internes

## Ce que ça change concrètement pour ton projet actuel

Dans ton repo actuel, les fichiers importants à adapter seront surtout:

- [public/index.php](./public/index.php)
- [config/bootstrap.php](./config/bootstrap.php)
- [public/.htaccess](./public/.htaccess)
- et potentiellement les chemins codés en dur comme dans [src/Repository/SettingRepository.php](./src/Repository/SettingRepository.php)

## Ma recommandation pratique

Je te conseille de penser le projet en 2 niveaux:

1. **Source**
   - ton dépôt actuel
   - versionné
   - maintenu comme une app Symfony normale

2. **Package**
   - généré par script
   - contient `index.php`, `install.php`, `.htaccess`, `app/`
   - livré à l’utilisateur final

Si tu veux, je peux maintenant te dessiner une **arborescence encore plus fidèle à ton projet actuel**, avec:
- où mettre `vendor/`
- où placer les configs locales
- et comment faire pointer `index.php` et `install.php` proprement.

> Toute la logique de l'installateur serait dans install.php ?

Pas idéalement, non.

Tu peux faire démarrer l’installateur depuis `install.php`, mais **toute la logique ne devrait pas vivre dans ce seul fichier**. Le mieux, c’est:

- `install.php` = **point d’entrée**
- des classes dédiées dans `app/src/Install/` ou `app/src/Installer/` = **logique métier**
- éventuellement quelques templates Twig ou vues PHP pour l’UI

## Ce que je conseille

### `install.php`
Il sert juste à:
- vérifier si l’installation est déjà faite
- charger l’autoloader
- créer l’objet principal de l’installateur
- lancer le contrôleur ou le routeur d’installation

En gros, c’est un bootstrap très fin.

### Classes dédiées
Tu mets la logique dans des classes comme:

- `EnvironmentChecker`
- `DatabaseTester`
- `SmtpTester`
- `ConfigWriter`
- `InstallerManager`

Ça te permet:
- de tester plus facilement
- d’éviter un gros fichier impossible à maintenir
- de réutiliser la logique si tu fais une version CLI plus tard

## Structure simple possible

```text
/
├── install.php
├── index.php
└── app/
    └── src/
        └── Install/
            ├── Installer.php
            ├── EnvironmentChecker.php
            ├── DatabaseTester.php
            ├── SmtpTester.php
            └── ConfigWriter.php
```

## Le bon compromis
Si tu veux aller vite pour une première version:
- `install.php` peut contenir la page d’accueil et le routage simple
- mais dès que tu as 2 ou 3 étapes, il faut extraire le code dans des classes

Donc:
- **oui**, `install.php` peut contenir le point d’entrée et un peu de logique légère
- **non**, je ne mettrais pas toute l’implémentation dedans

Si tu veux, je peux te proposer maintenant une **version concrète minimale** de `install.php` + les 3 premières classes à créer.
