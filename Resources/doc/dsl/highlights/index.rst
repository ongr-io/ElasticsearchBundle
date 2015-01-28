Highlights
----------

Allows to highlight search results on one or more fields. Simple usage example:

.. code:: php

    ...

    $field = new Field('title');

    $highlight = new Highlight();
    $highlight->setTag('tag')
        ->setFragmentSize(12)
        ->setNumberOfFragments(1)
        ->add($field);
        
    $search = $repository->createSearch()
            ->addQuery($query)
            ->setHighlight($highlight);

    $results = $repository->execute($search);
    
    $document = $results[0];
    $highlight = $document->getHighlight['title'];
    
Highlights are set into ``Highlight`` object (also global tags, order, highlighter type, fragment size, number of fragments 
can be set). To highlight a field add ``Field`` object with at least a field name to highlight. When all highlights are created
they are put in ``Search`` object. After data has been retrieved every document should have a method ``getHighlight`` which returns
array accessable object. To get highlight use highlighter field name as index.

Formed request query:

.. code:: javascript

    {
      "query": {
        ...
      },
      "highlight": {
        "post_tags": [
          "</tag>"
        ],
        "pre_tags": [
          "<tag>"
        ],
        "fields": {
          "title": {
            "fragment_size": 12,
            "number_of_fragments": 1,
            "type": "plain",
            "matched_fields": [
              "title"
            ],
            "force_source": true
          }
        }
      }
    }
    
You can also set other fields in ``Field`` object:

+------------------------------------------------+--------------------------------------------------------------------------------+
| Method                                         | Description                                                                    |
+================================================+================================================================================+
| ``setHighlighterType($type)``                  | Sets highlighter type (forces). Available options 'plain', 'postings', 'fvh'.  |
+------------------------------------------------+--------------------------------------------------------------------------------+
| ``setFragmentSize($fragmentSize)``             | Sets field fragment size.                                                      |
+------------------------------------------------+--------------------------------------------------------------------------------+
| ``setNumberOfFragments($numberOfFragments)``   | Sets maximum number of fragments to return.                                    |
+------------------------------------------------+--------------------------------------------------------------------------------+
| ``setMatchedFields($matchedFields)``           | Set fields to match.                                                           |
+------------------------------------------------+--------------------------------------------------------------------------------+
| ``setHighlightQuery(BuilderInterface $query)`` | Set query to highlight.                                                        |
+------------------------------------------------+--------------------------------------------------------------------------------+
| ``setNoMatchSize($noMatchSize)``               | Shows set length of a string even if no matches found.                         |
+------------------------------------------------+--------------------------------------------------------------------------------+
| ``setForceSource($forceSource)``               | Set to force highlighting from source.                                         |
+------------------------------------------------+--------------------------------------------------------------------------------+

.. note:: More about highlights can be found `here <http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/search-request-highlighting.html>`_.
