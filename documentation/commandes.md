# Ce que tu trouveras dans ce projet
* extension twig
* services.yaml
  * bind variable to service
  * parameter
* fileupload using flysystem (version without flysystem in old commits)
* caching images using liip
* fixtures
* Security 
  * Voters (Security/Voters)


## Démarrer serveur en local
```bash
$ symfony serve -d
```
Lien pour accéder à l'interface: https://127.0.0.1:8000

## Admin
user: admin1@thespacebar.com
mdp: engage
### Edit page
https://127.0.0.1:8000/admin/article/1/edit

# Uploading Files to Cloud 

### Install Flysytem (v3)
[Github][2]  
[Doc v3][3]
```bash
$ composer req oneup/flysystem-bundle:^3.0
```

### Configure Liip with Flysystem
Tutorial 17
[Liip+Flysystem][4]

# Tests (pas sur ce projet)
Jouer les tests (srs/tests):

```bash
$ php bin/phpunit
```

[1]: https://example.com
[2]: https://github.com/1up-lab/OneupFlysystemBundle
[3]: https://github.com/1up-lab/OneupFlysystemBundle/blob/release/3.x/Resources/doc/index.md
[4]: https://symfony.com/bundles/LiipImagineBundle/current/data-loader/flysystem.html