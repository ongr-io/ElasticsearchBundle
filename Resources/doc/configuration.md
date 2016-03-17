# Configuration tree

Here's an example of full configuration with all options:

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
    connections:
        default:
            hosts:
                - 127.0.0.1:9200
            index_name: ongr-default
            settings:
                refresh_interval: -1
                number_of_replicas: 1
            analysis:
              analyzer:
                - pathAnalyzer
              tokenizer:
                - pathTokenizer
        bar:
            hosts:
                - 10.0.0.1:9200 #default 127.0.0.1:9200
            index_name: ongr-bar
            settings:
                refresh_interval: 1 #default -1
                number_of_replicas: 0 #default 0
            analysis:
                filter:
                   - incremental_filter
    managers:
        default:
            connection: default
            logger: true #default %kernel.debug%
            mappings:
                - AcmeBarBundle #Scans all bundle documents
        foo:
            connection: bar
```
