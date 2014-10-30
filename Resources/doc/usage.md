### Manager

Elasticsearch bundle to communicate with elasticsearch index, it provides managers that could handle several indexes. 

Once you define managers in your `config.yml` file, you can use them in controllers and grab them from DI container via `es.manager` (alias for `es.manager.default`). If you define more than one manager, for example called customer, then it will be accessible via `es.manager.customer`.

````php
$manager = $this->get('es.manager');
````

> More about how to setup managers can be found in [Mapping](mapping.md) chapter.


### Create new document 

To save a new document, first create it, then persist it and finally, call commit on the document manager.

````php
$manager = $this->get("es.manager");

$content = new Content();
$content->title = "Acme";

$manager->persist($content); //adds to bulk container
$manager->commit(); //bulk data to index and flush
````

### Document repository

Manager provides repository access, which enables direct access to the elasticsearch type. Let's get Customer type repository from AcmeTestBundle.

### Find a document

````php
$manager = $this->get("es.manager");
$repository = $manager->getRepository('AcmeTestBundle:Customer');
$document = $repository->find(1); // 1 is the document id
````

### Remove a document

Removing via repository will remove document instantly. To remove document via bulk use manager instead.

````php
$manager = $this->get("es.manager");
$repository = $manager->getRepository('AcmeTestBundle:Customer');
$document = $repository->remove(1); // 1 is the document id
````

### Update document

````php
$manager = $this->get("es.manager");
$repository = $manager->getRepository('AcmeTestBundle:Customer');
$document = $repository->find(1); // 1 is the document id

$document->title = "Bar";

$manager->persist($document);
$manager->commit();
````

### Search a document

There are several ways how to search through the elasticsearch index, the easiest way is to find a document by it's id:

````php
$manager = $this->get("es.manager");
$repository = $manager->getRepository('AcmeTestBundle:Customer');
$document = $repository->find(1); // 1 is the document id
````

There is also a findBy function which searches using Terms query:
````php
$manager = $this->get("es.manager");
$repository = $manager->getRepository('AcmeTestBundle:Customer');
$documents = $repository->findBy(['title' => 'Acme']);
````

To perform a more complex queries there is a Search DSL API. Read more about it in [Search DSL](search.md) chapter.
