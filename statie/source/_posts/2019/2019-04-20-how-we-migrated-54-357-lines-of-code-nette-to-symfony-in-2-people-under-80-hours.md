---
id: 79
title: "How we Migrated 54 357-lines Application from Nette to Symfony in 2 People under 80 Hours"
perex: |
    It would take us 3 full-time months to rewrite this code in 2017. In February 2019 we did it in less than 3-week span with the help of automated tools. Why and how? 
author: 1
tweet: "New Post on #pehapkari Blog: How we Migrated 54 357 Lines of Code from #nettefw to #symfony in 2 People under 80 Hours   #casestudy"
---

*This post was originally published [in Czech on Zdrojak.cz](https://www.zdrojak.cz/clanky/50k-radku-z-nette-do-symfony/), where it got huge attention of Czech PHP community and hit amazing 54 comments (a possible record). But when I talk about this migration with my English speaking PHP friends, it seems crazy to them and they want to hear details - who, how, when, what exactly?* 

*This post is for you (and for you of course, if you haven't read it on Zdroják).*

<div class="text-center">
<img src="/assets/images/posts/2019/fw-migration/nette-to-symfony.png">
</div>

## What Have We Migrated?

Backend of [Entry.do](https://entry.do/) project - API application built on controllers, routing, Kdyby integrations of Symfony, Doctrine and a few Latte templates. The application has been running in production for last 4 years. We migrated from Nette 2.4 to Symfony&nbsp;4.2.

How big is it? If we don't count tests, migration, fixtures, etc., the application has **270 PHP files** in the length of **54 357 lines** (using [phploc](https://github.com/sebastianbergmann/phploc)).

How many unique routes does it have? 20...? 50...? **151!** 
Just to have an idea, the pehapkari.cz website has 35 routes.

## Why?

The application was written in Nette, which worked and met the technical requirements. The main motivation for the transcription was the dying ecosystem and that over-integration of Symfony. What does "dying ecosystem" mean? Nette released just 1 minor version since July 2016, while Symfony had 6 releases during the same period.
 

<div class="text-center mb-4">
    <img src="/assets/images/posts/2019/fw-migration/extensions.png">
    <br>
    <em>80% of the extensions are just glue integrations of Symfony and Doctrine</em>
</div>

<div class="text-center mb-4">
    <img src="/assets/images/posts/2019/fw-migration/nette-symfony.png">
    <br>
    <em>Nette was just Controllers, Routing and Dependency-Injection</em>
</div>

Why use unmaintained integrations of [Kdyby](https://github.com/kdyby) and [Zenify](https://github.com/zenify), that only integrate Symfony to Nette\DI, if Symfony is already there? Last new minor version of Nette was published 3 years ago. Symfony releases new minor version every 6 months with new features will make your work easier.

## How?

I offered [Honza Mikeš](http://github.com/JanMikes) deal he couldn't refuse: 

<blockquote class="blockquote text-center">
"We will give it a week and if we stuck, we'll give up".
</blockquote> 

On the January 27th, we met with his Nette application and on February 13th the Symfony application went to the staging server. **In less than 17 days we were done** and on February 14th we celebrated a new production application in addition to Valentine's Day.

<div class="text-center" markdown=1>
<img src="/assets/images/posts/2019/fw-migration/pull-request.png">
*The final size of migration pull-request* 
</div>

In fact, we were talking about migration at the beginning of 2017, because the Nette ecosystem wasn't really developing and Symfony was technologically skipping it. At that time, however, the transition would last at least 80-90 days for full-time, which is insane, so we didn't go into it.

## Tool Set

In 2019 we already have a **lot of tools to do the work for you**:

- The first is [Rector](https://github.com/rectorphp/rector), tool I made that can change any code that runs at least on PHP 5.3 from pattern A to pattern B. It can instantly update the code from PHP 5.3, 5.4, 5.5, 5.6... to 7.4, Symfony from 2.8 to 4.2, Laravel from static code to constructor injection, and more. You can add your own rules tailored to migrate your specific code, that can handle anything that PHP programmer can do (A → B) in a fraction of the time.

- The second is [NeonToYamlConverter](https://github.com/Symplify/NeonToYamlConverter) - as you can guess, it converts NEON syntax to YAML

- The third assistant is [LatteToTwigConverter](https://github.com/symplify/lattetotwigconverter) - it migrates Latte files to TWIG syntax

During those **17 days we put in 80 hours of work** for both of us together (= 40 hours each).

## 20 % of Good Old Manual Work

Although we do not like it, we had to do 20 % of the migration manually.

One of the first steps was to move from [config programming to PHP programming](https://www.tomasvotruba.cz/blog/2019/02/14/why-config-coding-sucks/). Both frameworks try to promote their own sugar syntax for Neon or YAML. It sounds cool to new programmers, to write less code, but it's confusing, framework-specific, can be done in clear PHP anyway, and most importantly, static analysis and instant refactoring won't deal with it.

How does "config programming" look like?

```yaml
services:
    - FirstService(@secondService::someMethod())
```

Or also:

```yaml
services:
    -
        class: 'Entrydo\Infrastructure\Payment\GoPay\NotifyUrlFactory'
        arguments:
            - '@http.request::getUrl()::getHostUrl()'
```

What common PHP pattern, that is framework-agnostic and almost everyone knows, can we use?

Factory!

```php
<?php

final class FirstServiceFactory
{
    /**
     * @var SecondService
     */
    private $secondService;

    public function __construct(SecondService $secondService)
    {
        $this->secondService = $secondService;
    }

    public function create()
    {
        return new SomeService($this->secondService);
    }
}
```

### What did we Gained with This Refactoring?

- Constructor injection!
- Framework independence - when we migrate to another framework or pattern in 3 years, we don't have to deal with this file anymore.
- Static analysis works
- More testable code, thanks to PHP code instead of config
- PHPStorm refactoring works
- Rector works
- It's a clear PHP code

<br>

In Nette and Symfony, several things were different:

- ErrorPresenter → [ExceptionSubscriber](https://symfony.com/doc/current/event_dispatcher.html%23creating-an-event-subscriber)
- Use [SymfonyTestBundle](https://symfony.com/doc/current/testing.html) for tests
- Moving Files to [Symfony 4 Single-Level Structure](http://fabien.potencier.org/symfony4-directory-structure.html)
- Exchange of services in mock tests
- In Nette, there is a Request/Response service, but in Symfony, it's an object
- Rewrite extension configs to Flex configs

## 80 % of Work Automated

Another 80% of the pull-request you saw above was done by automatic tools. The first one was enough to write, the other one to set it up.

### Neon to YAML

Neon and YAML are de facto fields [with minor differences in syntax](https://www.tomasvotruba.cz/blog/2018/03/12/neon-vs-yaml-and-how-to-migrate-between-them/), but when it comes to services, each framework writes a little differently. Config with services had 316 lines in the services section. You don't want to migrate it manually, the Neon entities. In addition, just one error in related migration and you can do it all over again.

I took few hours and wrote [Symplify/NeonToYamlConverter](https://github.com/Symplify/NeonToYamlConverter). Just pass the path to the `*.neon` file and it will convert into beautiful `*.yaml` file.

### PHP Migration

Again to the factory pattern - there were several custom Response classes in the code that inherited from Nette Response and added extra logic. We could edit them manually one by one, but it was easier to extract them into the factory method:


```diff
 <?php

 class SomePresenter
 {
+    /**
+     * @var ResponseFactory
+     */
+    private $responseFactory;
+
+    public function __construct(ResponseFactory $responseFactory)
+    {
+        $this->responseFactory = $responseFactory;
+    }   
+
     public function someAction()
     {
-        return new OKResponse($response);
+        return $this->responseFactory->createJsonResponse($response);
     }
 }
```

Honza created new `NewObjectToFactoryCreateRector` rule that handled this.

### What else was left?

- Move the routing from `RouterFactory` to particular Controller actions
- Rename `Request` and `Response` classes + including their codes (`POST`, `GET`, `200`...)
- Rename classes and methods on `Nette\DI\Container`, `Nette\Configurator`, `Nette\Application\IPresenter` etc,
- Changing parent classes on Controllers,
- Renaming Controllers to `*Controller` (they use "Presenter" naming in Nette)
- Move namespaces from `App\Presenter` to `App\Controller`

The most changes were in controllers:

```diff
 <?php declare (strict_types = 1);
 
-namespace App\Presenter;
+namespace App\Controller;
 
-use Nette\Application\AI\Presenter;
+use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
-use Nette\Http\Request;
+use Symfony\Component\HttpFoundation\Request;

-final class SomePresenter extends Presenter
+final class SomeController extends AbstractController
 {
-    public static function someAction()
+    public static function someAction(Request $request)
     {
-        $header = $this-> httpRequest-> getHeader('x');
+        $header = $request-> headers-> get('x');

-        $method = Request::POST;
+        $method = Request::METHOD_HPOST
     }
 }
```

## Syntax Sugar? Syntax Hell

For a while, Kdyby\Translation screwed us with "syntax sugar". In the Nette application, the listing of variables (Tom) worked for us:

- "Hi, my name is Tom"

But in Symfony magically added `%%`:

- "Hi, my name is% Tom%"

WTF? After 15 minutes we figured it out - Kdyby\Translation wrapped the variable name in "%%" for you - and fixed it:

```diff
 <?php

 class SomePresenter
 {
     public function someAction()
     {
         // Kdyby/Translation differnce to native Symfony/Translation
         $this->translations->translate('Hi, my name is %name%', [
-            'name' => 'Tom',
+            '%name%' => 'Tom'
         ]);
     }
 }
```

Pretty cool, huh?

## Rename Event Names

We also cannot forget the rename of events from Contribute\Events to Symfony [KernelEvents](https://symfony.com/doc/current/reference/events.html%23kernel-events):

<div class="text-center">
    <img src="/assets/images/posts/2019/fw-migration/event-rename.png">
</div>

## From `RouterFactory` to Controller `@Route` annotation

RouteFactory is [single class](https://github.com/nette/sandbox/blob/da9df83d6e07348d979f8673f15c512e984f4c53/app/router/RouterFactory.php%23L19) in Nette to define all routes for all controllers and their actions. In Symfony, this is quite the opposite. You define the routes directly at the Controller action. And to make matters worse it [uses annotation](https://symfony.com/doc/current/routing.html%23creating-routes).

What with this? Well, one option is to move one route at a time - **all 151**. To make it even more challenging, we had our own `RestRoute` and our own `RouteList`, including POST/GET/..., which Nette doesn't have.

How does one change look like?

```diff
 <?php

 namespace App;
 
 use Entrydo\RestRouteList;
 use Entrydo\Restart;

 final class RouterFactory
 {
-    private const PAYMENT_RESPONSE_ROUTE = '/ payment / process';
     // 150 more!
     
     public function create()
     {
         $router = new RestRouteList();
-        $router[] = RestRoute::get(self::PAYMENT_RESPONSE_ROUTE, ProcessGPWebPayResponsePresenter::class);
          // 150 more!
          
          return $router;
      }
 }
```

```diff
 namespace App Presenter;
  
+use Symfony\Component\Routing\Annotation\Route;
  
 final class ProcessGPWebPayResponsePresenter
 {
+    /**
+     * @Route(path = "/payments/gpwebpay/process-response", methods="GET"})
+     */
     public function __invoke()
     {
         // ...
     }
 }
```

Now do this 151 times... and make rebase-proof. When we first talked about the migration in 2017, we would make all these changes manually. **Too lazy to work.**

And in 2019? For a few days, we were preparing the `nette-to-symfony` Rector set and then run it on the entire code base:

```bash
composer require rector/rector -dev
vendor/bin/rector process app src --level nette-to-symfony
```

And it is done  :)

Everything we've learned during the 17-day migration is in this set and this post. Just download Rector and you can use the set straight away.

From Valentine's Day to the nette-to-symfony set, a complete migration from Nette Tester to PHPUnit and the migration of Nette Forms to Symfony Forms and Component to Controllers have been added.

## Final Touches

After a lot of static content changes, the code worked and the tests went through, but it looked a bit messy. Spaces were missing, fully qualified class names were not imported, etc.

You can use your own PHP_CodeSniffer and PHP-CS-Fixer set. We used the [Rector-prepared set of 7 rules](https://github.com/rectorphp/rector/blob/master/ecs-after-rector.yaml) with [EasyCodingStandard](https://github.com/symplify/easyCodingStandard/):

```bash
vendor/bin/ecs check app src --config vendor/rector/rector/ecs-after-rector.yaml
```

## It's not about the Work, It's about the Knowledge 

And so we migrated a 4-years old Nette application of 54 357 lines under 80 hours to Symfony and put it into production. The most of the time took us debugging of events and writing migration rules and tools. Now **the same application would take us (or you) 10 hours top to migrate**.

<br>

As you can see, any application can be migrate from one framework to another under a month. Dare us!
