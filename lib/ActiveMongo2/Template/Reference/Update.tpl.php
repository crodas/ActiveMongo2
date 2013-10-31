// <?php
// update all the references!
@foreach ($references[$doc['class']] as $ref)
    // update {{$doc['name']}} references in  {{$ref['collection']}} 
    $replicate = array();
    $target_id = array();
    @if ($ref['deferred']) 
        @if (!empty($deferred_done))
            @continue
        @end
        @set($deferred_done, true)
    @end
    foreach ($args[0] as $operation => $values) {
        @foreach ($ref['update'] as $field)
            if (!empty($values[{{@$field}}])) {
                @if ($ref['deferred'])
                    $replicate[$operation] = [{{@$field}}  => $values[{{@$field}}]];
                @elif ($ref['multi'])
                    $replicate[$operation] = [{{@$ref['property'].'.$.'.$field}}  => $values[{{@$field}}]];
                @else
                    $replicate[$operation] = [{{@$ref['property'].'.'.$field}} => $values[{{@$field}}]];
                @end
            }
        @end
    }


    @if ($ref['deferred']) 
        if (!empty($replicate)) {
            // queue the updates!
            $data = array(
                'update'    => $replicate,
                'processed' => false,
                'created'   => new \DateTime,
                'source_id' => {{@$doc['name'].'::'}}  . serialize($args[2]),
                'type'      => array(
                    'source'    => {{@$doc['name']}},
                    'target'    => {{@$ref['collection']}},
                ),
            );
            $args[1]
                ->getDatabase()
                ->deferred_queue
                ->save($data, array('w' => 0));

        }
        @continue
    @end

    if (!empty($replicate)) {
        // do the update
        $args[1]->getCollection({{@$ref['collection']}})
            ->update([
                '{{$ref['property']}}.$id' => $args[2]], 
                $replicate, 
                ['w' => 0, 'multi' => true]
            );
    }
@end
