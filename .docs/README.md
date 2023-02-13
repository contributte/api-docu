# Contributte\ApiDocu

ApiDocu can generate api documentation for routes created using [ApiRouter](https://github.com/contributte/api-router). It works either for directly defined routes or the ones defined via annotation.</p>

## Content

- [Content](#content)
- [Usage - runtime documentation](#usage---runtime-documentation)
    + [GET routes](#get-routes)
    + [PUT, POST, DELETE routes](#put--post--delete-routes)
    + [Presenter code:](#presenter-code-)
- [Generating API documentation](#generating-api-documentation)
    + [`?__apiDocuGenerate`](#----apidocugenerate-)
- [HTTP authorization](#http-authorization)

## Usage - runtime documentation

![Route docs](assets/route-docs.png)

### GET routes

ApiDocu can show you api documentation for current route, if there is any. Just visit the api url and add a `?__apiDocu` parameter in you address bar.

### PUT, POST, DELETE routes

But you cat match only routes with GET method when you are comming through browser window. Route method can by changed using `?__apiRouteMethod` query parameter. To visit PUT route api documentation you have to write something like that: `/users/10?__apiRouteMethod=DELETE&__apiDocu`. Here is an example: `/api-router/api/users/8?__apiRouteMethod=PUT&__apiDocu`.

### Presenter code:

```php
<?php

namespace App\ResourcesModule\Presenters;

use Nette;
use Contributte\ApiRouter\ApiRoute;

/**
 * API for managing users
 *
 * @ApiRoute(
 * 	"/api-router/api/users[/<id>]",
 * 	parameters={
 * 		"id"={
 * 			"requirement": "\d+",
 * 			"type": "integer",
 * 			"description": "User ID",
 * 			"default": 10
 * 		}
 * 	},
 *  priority=1,
 *  format="json",
 *  section="Users",
 *  presenter="Resources:Users"
 * )
 */
class UsersPresenter extends Nette\Application\UI\Presenter
{

	/**
	 * Get user detail
	 *
	 * You **can** also write example json in the description
	 *
	 * <json>
	 * {
	 * 	"name": "John",
	 * 	"surname": "Doe",
	 * 	"age": 23,
	 * 	"hairCount": 123456,
	 * 	"parents": {{
	 * 		"name": "John",
	 * 		"surname": "Doe",
	 *	 	"age": 53,
	 * 		"hairCount": 456
	 * 	}}
	 * }
	 * </json>
	 *
	 * @ApiRoute(
	 * 	"/api-router/api/users/<id>[/<foo>-<bar>]",
	 * 	parameters={
	 * 		"id"={
	 * 			"requirement": "\d+",
	 * 			"type": "integer",
	 * 			"description": "User ID",
	 * 			"default": 10
	 * 		}
	 * 	},
	 * 	method="GET",
	 * 	format="json",
	 * 	example={
	 * 		"name": "John",
	 * 		"surname": "Doe",
	 * 		"age": 23,
	 * 		"hairCount": 123456,
	 * 		"parents": {{
	 * 			"name": "John",
	 *			"surname": "Doe",
	 * 			"age": 53,
	 * 			"hairCount": 456
	 * 		}}
	 * 	},
	 * 	tags={
	 * 		"public",
	 * 		"secured": "#e74c3c"
	 * 	},
	 * 	response_codes={
	 *  	200="Success",
	 *  	400="Error in authentication process",
	 *  	401="Invalid authentication"
	 *  }
	 * )
	 */
	public function actionRead($id, $foo = NULL, $bar = NULL)
	{
		$this->sendJson(['id' => $id, 'foo' => $foo, 'bar' => $bar]);
	}


	public function actionUpdate($id)
	{
		$this->sendJson(['id' => $id]);
	}


	public function actionDelete($id)
	{
		$this->sendJson(['id' => $id]);
	}

}
```

## Generating API documentation

![Docs](assets/docs.png)

### `?__apiDocuGenerate`

When you are directly on some api url, you can use query parameter `?__apiDocuGenerate` for generating whole application api documentation. All documentation files will be available in directory specified by you. By default, the directory is:

```neon
apiDocu:
	apiDir: "%wwwDir%/api"


extensions:
	apiRouter: Contributte\ApiRouter\DI\ApiRouterExtension
	apiDocu: Contributte\ApiDocu\DI\ApiDocuExtension
```

Example api generation trigger is here: `/api-router/api/books?__apiDocuGenerate`.

## HTTP authorization

You can use a HTTP authorization on your documentation sites:

```neon
apiDocu:
	apiDir: "%wwwDir%/client-api"
	httpAuth:
		user: foo
		password: bar
```
