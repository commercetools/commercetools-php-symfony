Create a new symfony project and open the project.

Open composer.json and add to the attribute require this line

        "commercetools/symfony-bundle": “dev-checkout",
        "symfony/assetic-bundle": "^2.8" 

Open App/AppKernel.php and these lines 
	
            new Commercetools\Symfony\CtpBundle\CtpBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle()

Open parameters.yml.dist and add these lines 
	
	commercetools.client_id: ~
    	commercetools.client_secret: ~
    	commercetools.project: ~

Open config.yml and these lines on the bottom of the document
	
	commercetools:
 	 credentials:
   	 client_id: "%commercetools.client_id%"
  	  client_secret: "%commercetools.client_secret%"
   	 project: "%commercetools.project%"
  	cache:
   		 product: true
 		 currency:
 	   DE: EUR
   	 UK: GBP

assetic:
    debug:          '%kernel.debug%'
    use_controller: '%kernel.debug%'
    filters:
        cssrewrite: ~


Open the console and go to the right directory and type the command “composer update” After a while the question will appear add the credentials. 
These you can find in admin.sphere.io -> developers -> api clients -> Select project -> Client Credential Flow

Open App/config/routing_dev.yml and add these lines between _errors: and _main: 

_example:
    resource: "@CtpBundle/Resources/config/routing.yml"
    prefix: /_example
