<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Mapping\Proxy;

use Doctrine\Common\Inflector\Inflector;

/**
 * Generates proxy classes for documents.
 */
class ProxyFactory
{
    /**
     * Returns proxy namespace.
     *
     * @param \ReflectionClass $reflectionClass Original class reflection.
     * @param bool             $withName        Includes class name also.
     *
     * @return string
     */
    public static function getProxyNamespace(\ReflectionClass $reflectionClass, $withName = true)
    {
        $namespace = $reflectionClass->getNamespaceName() . '\\_Proxy';

        if ($withName) {
            $namespace .= '\\' . $reflectionClass->getShortName();
        }

        return $namespace;
    }

    /**
     * Generates proxy class with setters and getters by reflection.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return string
     */
    public static function generate(\ReflectionClass $reflectionClass)
    {
        $code = static::getHeader(
            [
                'namespace' => static::getProxyNamespace($reflectionClass, false),
                'class' => $reflectionClass->getShortName(),
                'base' => $reflectionClass->getName(),
            ]
        );

        /** @var \ReflectionProperty $property */
        foreach ($reflectionClass->getProperties() as $property) {
            $name = $property->getName();
            $methodName = ucfirst(Inflector::classify($name));

            if (!$reflectionClass->hasMethod("get{$methodName}")) {
                $code .= static::getMethod(
                    [
                        'name' => "get{$methodName}",
                        'content' => "return \$this->{$name};",
                    ]
                );
            }

            if (!$reflectionClass->hasMethod("set{$methodName}")) {
                $code .= static::getMethod(
                    [
                        'name' => "set{$methodName}",
                        'content' => "\$this->{$name} = \${$name};",
                        'params' => "\$$name",
                    ]
                );
            }
        }
        $code .= static::getFooter();

        return $code;
    }

    /**
     * Gives php class file header.
     *
     * @param array $options
     *
     * @return string
     */
    private static function getHeader(array $options)
    {
        extract($options);

        return <<<EOF
<?php

namespace $namespace;

use $base as Base;

class $class extends Base
{

EOF;
    }

    /**
     * Generates method.
     *
     * @param array $options
     *
     * @return string
     */
    private static function getMethod(array $options)
    {
        extract($options);
        $out = <<<EOF
    public function $name(
EOF;

        if (isset($params)) {
            $out .= $params;
        }
        $out .= ")\n";
        $out .= <<<EOF
    {
        $content
    }

EOF;

        return $out;
    }

    /**
     * Gives php class file footer.
     *
     * @return string
     */
    private static function getFooter()
    {
        return "}\n";
    }
}
