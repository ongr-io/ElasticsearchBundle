Document Inheritance
====================

By default annotations inherit all properties that have been defined in parent document. We have implemented ``Skip`` and ``Inherit`` annotations to make inheritance to be less of a headache. E.g.:

.. code:: php

    /**
     * Document class Item.
     *
     * @ES\Document(create=false)
     */
    class Item extends AbstractDocument
    {
        /**
         * @var string
         *
         * @ES\Property(name="name", type="string")
         */
        public $name;

        /**
         * @var float
         *
         * @ES\Property(type="float", name="price")
         */
        public $price;

        /**
         * @var \DateTime
         * 
         * @ES\Property(name="created_at", type="date")
         */
        public $createdAt;
    }

    /**
     * Product document for testing.
     *
     * @ES\Document(type="product")
     * @ES\Skip({"name"})
     * @ES\Inherit({"price"})
     */
    class Product extends Item
    {
        /**
         * @var string
         *
         * @ES\Property(type="string", name="title", fields={@ES\MultiField(name="raw", type="string")})
         */
        public $title;

        /**
         * @var string
         *
         * @ES\Property(type="string", name="description")
         */
        public $description;

        /**
         * @var int
         *
         * @ES\Property(type="integer", name="price")
         */
        public $price;
    }

In this example Product document inherits all properties from Item. Let’s pretend that we don't want property ``$name`` in Product document. So we just add ``@ES\Skip("name")`` or if we want multiple skip's ``@ES\Skip({"price", "description"})``.

.. note:: Annotations above class are not inherited.

Next imagine that we want to extend this product document later but it should have price as integer (not float like Item), but this Type should have it as float and we don't want to rewrite price as integer everywhere. That's where ``@ES\Inherit`` kicks in. **It inherits properties from parent documents if it has been defined in current document**. So product document in this example will have price as float.
