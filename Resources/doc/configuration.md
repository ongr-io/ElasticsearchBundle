# Configuration tree

Here's an example of full configuration with all possible options including default values:

```yml
ongr_elasticsearch:
    analysis:
      analyzer:
          pathAnalyzer:
              type: custom
              tokenizer: pathTokenizer
      tokenizer:
          pathTokenizer:
              type : path_hierarchy
              buffer_size: 2024
              skip: 0
              delimiter: /
      filter:
          incremental_filter:
              type: edge_ngram
              min_gram: 1
              max_gram: 20
    managers:
        default:
            index: 
                hosts:
                    - 127.0.0.1:9200
                index_name: ongr-default
                settings:
                    refresh_interval: -1
                    number_of_replicas: 0
                    number_of_shards: 1
            logger: true #default %kernel.debug%
            mappings:
                - AcmeBarBundle #Scans all bundle documents
        custom:
            index: 
                hosts:
                    - 10.0.0.1:9200 #default 127.0.0.1:9200
                index_name: ongr-custom
                mappings:
                    AcmeBundle:
                        document_dir: Document
```