if (empty($this->loaded[{{@$self->getPath()}}])) {
    require_once __DIR__ . {{@$self->getPath()}};
    $this->loaded[{{@$self->getPath()}}] = true;
}
@if ($self->isMethod())
@else
    $return = \{{$self->getFunction()}}(
        {{$var}}, // document variable 
        {{@$args}}, // annotation arguments
        $this->connection, // connection
        empty($args) ? [] : $args,  // external arguments (defined at run time)
        $this // mapper instance
    );
@end
