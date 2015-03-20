@set($deferred_done, false)
@foreach ($collection->getForwardReferences() as $ref)
    // update {{$collection->getName()}} references in  {{$ref['property']->getParent()->getName()}} 
    // {{ $ref['deferred'] ? 'ues' : 'no' }}
    @if ($ref['deferred'])
        @if (!empty($deferred_done))
            @continue
        @end
    @end
    
    $replicate = array();
    @set($deferred_done, true)
    foreach ($args[0] as $operation => $values) {
        @foreach ($ref['update'] as $field)
            if (!empty($values[{{@$field}}])) {
                @if ($ref['deferred'])
                    $replicate[$operation][{{@$field}}]  = $values[{{@$field}}];
                @elif ($ref['multi'])
                    $replicate[$operation][{{@$ref['property']->getName().'.$.'.$field}}] = $values[{{@$field}}];
                @else
                    $replicate[$operation][{{@$ref['property']->getName().'.'.$field}}] = $values[{{@$field}}];
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
                'source_id' => {{@$collection->getName().'::'}}  . serialize($args[2]),
                'type'      => array(
                    'source'    => {{@$collection->getName()}},
                    'target'    => {{@$ref['property']->getParent()->getName()}},
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
        $args[1]->getCollection({{@$ref['property']->getParent()->getName()}})
            ->update([
                '{{$ref['property']->getName()}}.$id' => $args[2]], 
                $replicate, 
                ['w' => 0, 'multi' => true]
        );
    }
@end
