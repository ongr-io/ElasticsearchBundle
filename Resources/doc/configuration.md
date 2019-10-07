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
    source_directories: 
        - /src/AppBundle
    logger: false 
    profiler: true
    cache: true
    indexes: # overrides any index related config from anotations
        App\Document\Page:
            default: true
            hosts:
                - 'elasticsearch:9200'
            settings:
                number_of_replicas: 2
                number_of_shards: 3
            type: page # for 5.x ES compatibility
```
