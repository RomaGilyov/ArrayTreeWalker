# ArrayTreeWalker

## Installation ##

```php
composer require rgilyov/array-tree-walker dev-master
```

## Description ##

Lets make an example of array with tree structure:

```php
$tree = [
    'name'    => 'child',
    'parents' => [
        'mother' => [
            'name'    => 'mother',
            'parents' => [
                'mother' => [
                    'name' => 'first grand mother'
                ],
                'father' => [
                    'name' => 'first grand father'
                ]
            ]
        ],
        'father' => [
            'name'    => 'father',
            'parents' => [
                'mother' => [
                    'name' => 'second grand mother'
                ],
                'father' => [
                    'name'    => 'second grand father',
                    'parents' => [
                        'mother' => [
                            'name' => 'second grand grand mother'
                        ]
                    ]
                ]
            ]
        ]
    ]
];

$walker = new \RGilyov\ArrayTreeWalker($tree);
```

To walk through tree structure use `->`, to get value use `->get('name')`;

```php
echo $walker->parents->mother->parents->father->get('name'); // result: `first grand father`
```

You can also get value like so

```php
echo $walker->parents->mother->parents->father['name']; // result: `first grand father`

echo $walker->parents->mother->parents->father->name['name']; // result: `first grand father`

echo $walker->parents->mother->parents->father->name->father['name']; // result: null
```


You may also specify node name, just pass it as the second the class's constructor parameter

```php
$walkerWithNodeName = new \RGilyov\ArrayTreeWalker($tree, 'parents');

echo $walkerWithNodeName->father->father->get('name'); // result: `second grand father`

echo $walkerWithNodeName->father->father->mother['name']; // result: 'second grand grand mother'

echo $walkerWithNodeName->father->father->mother->mother['name']; // result: null
```

You also can walk down the tree and walk up the tree and modify each node during walking:

```php

$walker = new \RGilyov\ArrayTreeWalker($tree, 'parents');

/*
 * If an array will be returned the values for given keys will be rewritten
   and if keys didn't exist in the node they will be attached
 */
$walker->walkDown(function ($node, $level, $nodeNumber, $parentNodeNumber) {

    return [
        'level' => $level,
        'node_number' => $nodeNumber,
        'parent_node_number' => $parentNodeNumber
    ];

});

$walker->walkUp(function ($node, $nodeChildren, $parameters, $level, $nodeNumber) {

    return [
        'super_name' => $node['name'] . ' ' . implode(array_map(function ($child) {
            return $child['name'];
        }, $nodeChildren), ' ')
    ];

});

```

List of available methods:

```php
$walker->toArray();
$walker->count();
$walker->offsetExists('key');
$walker->offsetUnset('key');
$walker->offsetGet('key');
$walker->offsetSet('key', 'value');
```
