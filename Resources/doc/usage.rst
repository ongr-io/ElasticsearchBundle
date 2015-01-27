Usage
=====

Manager
-------

Elasticsearch bundle provides managers able to handle several indexes to communicate with elasticsearch.

Once you define managers in your ``config.yml`` file, you can use them in controllers and grab them from DI container via ``es.manager`` (alias for ``es.manager.default``). If you define more than one manager, for example called customer, then it will be accessible via ``es.manager.customer``.

.. code:: php

    $manager = $this->get('es.manager');

.. note:: More about how to setup managers can be found in `mapping <mapping.html>`_ chapter.

Create new document
~~~~~~~~~~~~~~~~~~~

To save a new document, first create it, then persist it and finally, call commit on the document manager.

.. code:: php

    $manager = $this->get("es.manager");

    $content = new Content();
    $content->title = "Acme";

    $manager->persist($content); //adds to bulk container
    $manager->commit(); //bulk data to index and flush

Document repository
~~~~~~~~~~~~~~~~~~~

Manager provides repository access, which enables direct access to the
elasticsearch type. Let’s get Customer type repository from
AcmeTestBundle.

Find a document
~~~~~~~~~~~~~~~

.. code:: php

    $manager = $this->get("es.manager");
    $repository = $manager->getRepository('AcmeTestBundle:Customer');
    $document = $repository->find(1); // 1 is the document id

Remove a document
~~~~~~~~~~~~~~~~~

Removing via repository will remove document instantly. To remove
document via bulk use manager instead.

.. code:: php

    $manager = $this->get("es.manager");
    $repository = $manager->getRepository('AcmeTestBundle:Customer');
    $document = $repository->remove(1); // 1 is the document id

Update document
~~~~~~~~~~~~~~~

.. code:: php

    $manager = $this->get("es.manager");
    $repository = $manager->getRepository('AcmeTestBundle:Customer');
    $document = $repository->find(1); // 1 is the document id

    $document->title = "Bar";

    $manager->persist($document);
    $manager->commit();

Search a document
~~~~~~~~~~~~~~~~~

There are several ways how to search through the elasticsearch index, the easiest way is to find a document by it’s id:

.. code:: php

    $manager = $this->get("es.manager");
    $repository = $manager->getRepository('AcmeTestBundle:Customer');
    $document = $repository->find(1); // 1 is the document id

There is also a findBy function which searches using Terms query:

.. code:: php

    $manager = $this->get("es.manager");
    $repository = $manager->getRepository('AcmeTestBundle:Customer');
    $documents = $repository->findBy(['title' => 'Acme']);

To perform a more complex queries there is a Search DSL API. Read more about it in `Searching via DSL <dsl/index.html>`_ chapter.
