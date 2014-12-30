@foreach ($collection->getBackReferences() as $ref)
    @if ($ref['deferred'])
        @if ($ev == "postCreate")
            $check = !empty($args[0][{{@$ref['property']->getName()}}]);
        @else
            $check = !empty($args[0]['$set'][{{@$ref['property']->getName()}}]);
        @end
        if ($check) {
            @if ($ref['multi'])
                $data = array();
                @if ($ev == "postCreate")
                    $fields = $args[0][{{@$ref['property']->getName()}}];
                @else
                    $fields = $args[0]['$set'][{{@$ref['property']->getName()}}];
                @end
                foreach ($fields as $id => $row) {
                    $data[] = array(
                        @if ($ev == "postCreate")
                        'source_id' => {{@$ref['target']->getName() . '::'}} . serialize($row['$id']),
                        'id'        => $args[0]['_id'],
                        @else
                        'source_id' => {{@$ref['target']->getName() . '::'}} . serialize($row['$id']),
                        'id'        => $args[2],
                        @end
                        'property'  => {{@$ref['property']->getName() . '.'}} . $id,
                    );
                }
            @else
                $data = array(array(
                    @if ($ev == "postCreate")
                    'source_id'     => {{@$ref['target']->getName() . '::'}} . serialize($args[0][{{@$ref['property']->getName()}}]['$id']),
                    'id'            => $args[0]['_id'],
                    @else
                    'source_id'     => {{@$ref['target']->getName() . '::'}} . serialize($args[0]['$set'][{{@$ref['property']->getName()}}]['$id']),
                    'id'            => $args[2],
                    @end
                    'property'      => {{@$ref['property']->getName()}},
                ));
            @end
            foreach ($data as $row) {
                $row['collection'] = $this->ns_by_name[{{@$ref['property']->getParent()->getName()}}] . {{@$ref['property']->getParent()->getName()}};
                $row['_id'] = array(
                    'source' => $row['source_id'], 
                    'target_id' => $row['id'], 
                    'target_col' => $row['collection'], 
                    'target_prop' => $row['property']
                );
                $col->save($row, array('w' => 1));
            }
        }
    @end
@end
