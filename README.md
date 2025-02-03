# Sauvegarde et restaure le contenu d'une base de données

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dimtrovich/db-dumper.svg?style=flat-square)](https://packagist.org/packages/dimtrovich/db-dumper)
[![Tests](https://img.shields.io/github/actions/workflow/status/dimtrovich/php-db-dumper/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/dimtrovich/php-db-dumper/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/dimtrovich/db-dumper.svg?style=flat-square)](https://packagist.org/packages/dimtrovich/db-dumper)


**Db Dumper** est un outils qui vous offre un moyen simple et efficace **d'exporter** et **d'importer** votre base de données en PHP. Il est en quelque sorte une version PHP de l'outil en ligne de commande `mysqldump` qui vient avec MySQL, sans dépendances, avec compression de sortie et des paramètres par défaut raisonnables.

Db Dumper prend en charge la sauvegarde des structures de table, des données elles-mêmes, des vues, des déclencheurs et des événements.

## Caractéristiques

Db Dumper prend en charge :
* la sortie des blobs binaires sous forme hexadécimale.
* la résolution des dépendances des vues (en utilisant des tables de substitution).
* la sauvegarde des routines stockées (fonctions et procédures).
* la sauvegarde des événements.
* l'insertion étendue et/ou complète.
* les colonnes virtuelles de MySQL 5.7.
* l'`insert-ignore`, comme un `REPLACE` mais en ignorant les erreurs si une clé en double existe.
* la modification des données de la base de données à la volée lors de la sauvegarde, en utilisant des `hooks`.
* la sauvegarde directe vers le stockage Google Cloud via un wrapper de flux compressé (GZIPSTREAM).

Db Dumper est conçu pour fonctionner avec les principaux système de gestion de base de données actuels. La liste ci-dessous dresse un état de leurs prise en charge :
* MySQL (supporté)
* SQLite (supporté)
* PostgreSQL (en cours d'implémentation)
* Oracle (pas supporté)

## Pré-requis

- PHP 7.4+
- *MySQL 5+
- *SQLite 3+
- [PDO](https://secure.php.net/pdo)

## Installation

En utilisant [Composer](https://getcomposer.org/) :

```
$ composer require dimtrovich/db-dumper
```

Après avoir installer ce package, vous devez au préalable vous assurez d'avoir accès à une instance `PDO` car les systèmes d'exportation et d'importation en n'ont besoin.

```php
use PDO;

$pdo = new PDO('mysql:host=localhost;port=3307;dbname=database', 'username', 'password');
```

## Exportation des données (dump)

L'exportation des données est la fonctionnalité principale de ce package. **Db Dumper** vous offre une API simple pour sauvegarder votre base de données avec les mêmes options offertes par les commandes natives de MySQL ou PostgreSQL (`mysqldump` / `pgrestore`) 

```php
use Dimtrovich\DbDumper\Exporter;
use Exception;

try {
    $exporter = new Exporter($pdo, 'database');
    
    $exporter->process('storage/work/dump.sql');
} catch (Exception $e) {
    echo 'db-dumper error: ' . $e->getMessage();
}
```

### Modification des valeurs lors de l'exportation

Vous pouvez enregistrer un callable qui sera utilisé pour transformer les valeurs lors de l'exportation. Un cas d'utilisation typique est la suppression de données sensibles des sauvegardes de base de données :

```php
$exporter = new Exporter($pdo, 'database');
    
$exporter->transformTableRow(function (string $tableName, array $row) {
    if ($tableName === 'customers') {
        $row['social_security_number'] = (string) rand(1000000, 9999999);
    }

    return $row;
});

$exporter->process('storage/work/dump.sql');
```

### Obtenir des informations sur l'exportation des tables

Vous pouvez enregistrer un callable qui sera utilisé pour rapporter la progression de la sauvegarde :

```php
$exporter->onTableExport(function($tableName, $rowCount) {
    echo $tableName . ' exporté';
    echo $rowCount . ' données';
});
```

### Conditions d'exportation spécifiques à une table

Vous pouvez définir des clauses `WHERE` spécifiques à une table pour limiter les données des tables qui pourront être exportées. Ces clauses remplacent le paramètre `where` par défaut :

```php
$exporter->setTableWheres([
    'users' => 'date_registered > NOW() - INTERVAL 3 MONTH AND deleted=0',
    'logs' => 'date_logged > NOW() - INTERVAL 1 DAY',
    'posts' => 'active=1'
]);
```

### Limites d'exportation spécifiques à une table

Vous pouvez également définir des limites spécifiques à une table pour limiter le nombre d'enregistrement qui seront sauvegardés par chaque table :

```php
$exporter->setTableLimits([
    'users' => 300,
    'logs' => 50,
    'posts' => 10
]);
```

### Options de configuration de l'exportateur

Le constructeur de la classe `Exporter` accepte un troisième paramètre qui est un tableau désignant les options d'exportation des données.  

<div class="overflow-auto">

Option  | Type | Défaut | Description
------- | ------- | ------- | -------
`include-tables` | `array` | `[]` | Inclure uniquement ces tables (tableau de noms de tables), inclure toutes si vide.  
`exclude-tables` | `array` | `[]` | Exclure ces tables (tableau de noms de tables), inclure toutes si vide, supporte les expressions régulières.    
`include-views` | `array` | `[]` | Inclure uniquement ces vues (tableau de noms de vues), inclure toutes si vide. Par défaut, toutes les vues nommées dans le tableau `include-tables` sont incluses.
`compress` | `Gzip`, `Bzip2`, `None`, `GzipStream` | `None` |   
`init_commands` | `array` | `[]` |   
`no-data` | `array` | `[]` | Ne pas sauvegarder les données pour ces tables (tableau de noms de tables), supporte les expressions régulières.
`if-not-exists` | `bool` | `false` | Créer une nouvelle table uniquement si une table du même nom n'existe pas déjà. Aucun message d'erreur n'est généré si la table existe déjà.  
`reset-auto-increment` | `bool` | `false` | Supprime l'option `AUTO_INCREMENT` de la définition de la base de données. Utile lorsqu'il est utilisé avec `no-data`, de sorte que lorsque la base de données est recréée, elle commence à 1 au lieu d'utiliser une ancienne valeur.  
`add-drop-database` | `bool` | `false` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-drop-database)  
`add-drop-table` | `bool` | `false` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-drop-table)  
`add-drop-trigger` | `bool` | `true` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-drop-trigger)
`add-locks` | `bool` | `true` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-locks)
`complete-insert` | `bool` | `false` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_complete-insert)  
`databases` | `bool` | `false` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_databases)  
`default-character-set` | `utf8`, `utf8mb4`, `binary` | `utf8` |   
`disable-keys` | `bool` | `true` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_disable-keys)    
`extended-insert` | `bool` | `true` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_extended-insert)
`events` | `bool` | `false` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_events) 
`hex-blob` | `bool` | `true` | (Plus rapide que le contenu échappé). [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_hex-blob)
`insert-ignore` | `bool` | `false` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_insert-ignore)
`lock-tables` | `bool` | `true` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_lock-tables)
`net_buffer_length` | `int` | `1000000` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_net_buffer_length)
`no-autocommit` | `bool` | `true` | Option pour désactiver l'autocommit (insertions plus rapides, pas de problèmes avec les clés d'index). [Documentation MySQL](https://dev.mysql.com/doc/refman/4.1/en/commit.html)
`no-create-db` | `bool` | `false` | Option pour désactiver la sauvegarde des instructions de création de base de données. [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_no-create-db)
`no-create-info` | `bool` | `false` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_no-create-info)
`routines` | `bool` | `false` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_routines)
`single-transaction` | `bool` | `true` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_single-transaction)
`skip-triggers` | `bool` | `false` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_triggers)
`skip-tz-utc` | `bool` | `false` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_tz-utc)  
`skip-comments` | `bool` | `false` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_comments)  
`skip-dump-date` | `bool` | `false` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_dump-date)
`skip-definer` | `bool` | `false` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.7/en/mysqlpump.html#option_mysqlpump_skip-definer)  
`where` | `string` | `''` | [Documentation MySQL](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_where)
</div>

## Erreurs

Pour sauvegarder une base de données, vous avez besoin des privilèges suivants :

- **SELECT**
  - Pour sauvegarder les structures de table et les données.
- **SHOW VIEW**
  - Si une base de données contient des vues, sinon vous obtiendrez une erreur.
- **TRIGGER**
  - Si une table contient un ou plusieurs déclencheurs.
- **LOCK TABLES**
  - Si l'option "lock tables" est activée.

Utilisez **SHOW GRANTS FOR user@host;** pour connaître les privilèges de l'utilisateur. Voir le lien suivant pour plus d'informations :

[Quels sont les privilèges minimum requis pour obtenir une sauvegarde du schéma d'une base de données MySQL ?](https://dba.stackexchange.com/questions/55546/which-are-the-minimum-privileges-required-to-get-a-backup-of-a-mysql-database-sc/55572#55572)

## Tests

Le code actuel pour les tests est un hack peu élégant. Il y a probablement de bien meilleures façons de les réaliser en utilisant PHPUnit, donc les PR sont les bienvenues. Le script de test crée et peuple une base de données en utilisant tous les types de données possibles. Ensuite, il l'exporte en utilisant à la fois `mysqldump-php` et `mysqldump`, et compare les sorties. Les tests sont OK uniquement si elles sont identiques. Après [ce commit](https://github.com/ifsnop/mysqldump-php/commit/8496fbb1b26dde404804bc8865ec32044da5b813), certains tests sont effectués en utilisant PHPUnit.

Certains tests sont ignorés si le serveur MySQL ne les supporte pas.

Quelques tests comparent uniquement entre le code SQL original et le code SQL généré par `mysqldump-php`, car certaines options ne sont pas disponibles dans `mysqldump`.

## Bugs (de mysqldump, pas de mysqldump-php)

Après [ce rapport de bug](https://bugs.mysql.com/bug.php?id=80150), un nouveau bug a été introduit. `_binary` est également ajouté lorsque l'option `hex-blob` est utilisée, si la valeur est vide.

## Todo

Écrire plus de tests, tester avec MariaDB également.

## Contribution

Veuillez consulter [CONTRIBUTING](CONTRIBUTING.md) pour plus de détails.

## Failles de sécurité

Veuillez consulter [notre politique de sécurité](../../security/policy) pour savoir comment signaler les vulnérabilités de sécurité.

## Credits

- [Dimitri Sitchet Tomkeu](https://github.com/dimtrovich)
- [Tous les Contributeurs](../../contributors)

## Licence

Ce projet est un logiciel open-source sous licence [MIT](MIT). Veuillez consulter [Fichier de licence](LICENSE.md) pour plus d'informations.

## Crédits

Après plus de 8 ans, il reste à peine quelque chose du code source original, mais :

À l'origine basé sur le script de James Elliott de 2009.
https://code.google.com/archive/p/db-mysqldump/

Adapté et étendu par Michael J. Calkins.
https://github.com/clouddueling

Actuellement maintenu, développé et amélioré par Diego Torres.
https://github.com/ifsnop
