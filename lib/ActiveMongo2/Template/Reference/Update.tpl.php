// update all the references!
@foreach ($references[$doc['class']] as $ref)
    // update {{{$doc['name']}}} references in  {{{$ref['collection']}}} 
    $replicate = array();
    $target_id = array();
    foreach ($args[0] as $operation => $values) {
        @foreach ($ref['update'] as $field)
            if (!empty($values[{{@$field}}])) {
                @if ($ref['multi'])
                    $replicate[$operation] = ["{{{$ref['property']}}}.$.{{{$field}}}" => $values["{{{$field}}}"]];
                @else
                    $replicate[$operation] = ["{{{$ref['property']}}}.{{{$field}}}" => $values["{{{$field}}}"]];
                @end
            }
        @end
    }

    if (!empty($replicate)) {
        @if ($ref['deferred']) 
            // queue the updates!
            $data = array(
                'update'    => $replicate,
                'processed' => false,
                'created'   => new \DateTime,
                'source_id' => {{@$doc['name'].'::'}}  . $args[2],
                'type'      => array(
                    'source'    => {{@$doc['name']}},
                    'target'    => {{@$ref['collection']}},
                ),
            );
            $args[1]
                ->getDatabase()
                ->deferred_queue
                ->save($data, array('w' => 0));
        @else
            // do the update
            $args[1]->getCollection({{{@$ref['collection']}}})
                ->update([
                    '{{{$ref['property']}}}.$id' => $args[2]], 
                    $replicate, 
                    ['w' => 0, 'multi' => true]
                );
        @end
    }
@end
