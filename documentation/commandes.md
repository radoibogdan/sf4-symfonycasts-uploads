# Ce que tu trouveras dans ce projet
* extension twig
* services.yaml
  * bind variable to service
  * parameter
* fileupload using flysystem (version without flysystem in old commits)
  * private file upload using streams
  * multiple file upload system using Dropzone JS library
* caching images using liip
* fixtures
* Security 
  * Voters (Security/Voters)
* ValidatorInterface + Constraints in Controller 
* Ajax requests / json routes
* Sorting items using sortablejs JS library

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

## Multiple file upload using Dropzone
Install with encore   
OR [DropZone JS library CDN][5] (used in this project)
- min.js (Copy Script Tag with SRI) + min css

## Sortable list article references using SortableJs Js library
Install with encore and import when using it
or [SortableJs Js library][6]

# Tests (pas sur ce projet)
Jouer les tests (srs/tests):

```bash
$ php bin/phpunit
```

[1]: https://example.com
[2]: https://github.com/1up-lab/OneupFlysystemBundle
[3]: https://github.com/1up-lab/OneupFlysystemBundle/blob/release/3.x/Resources/doc/index.md
[4]: https://symfony.com/bundles/LiipImagineBundle/current/data-loader/flysystem.html
[5]: https://cdnjs.com/libraries/dropzone
[5]: https://www.jsdelivr.com/package/npm/sortablejs