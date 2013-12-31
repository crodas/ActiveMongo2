if (empty($this->loaded[{{@$self->getPath()}}])) {
    require_once __DIR__ . {{@$self->getPath()}};
    $this->loaded[{{@$self->getPath()}}] = true;
}
@if ($self->isMethod())
    @if ($self->isPublic())
        @if ($self->isStatic())
            $return = \{{$self->getClass()}}::{{$self->getMethod()}}(
        @else
            $return = {{$self->getInstance()}}->{{$self->getMethod()}}(
        @end
            {{$var}}, // document variable 
            {{@$args}}, // annotation arguments
            $this->connection, // connection
            empty($args) ? [] : $args,  // external arguments (defined at run time)
            $this // mapper instance
        );
    @else
        $reflection = new \ReflectionMethod({{@"\\". $self->getClass()}}, {{@$self->getMethod()}});
        $reflection->setAccessible(true);
        $return = $reflection->invoke(
            {{$var}}, // document variable 
            {{@$args}}, // annotation arguments
            $this->connection, // connection
            empty($args) ? [] : $args,  // external arguments (defined at run time)
            $this // mapper instance
        );
    @end
@else
    $return = \{{$self->getFunction()}}(
        {{$var}}, // document variable 
        {{@$args}}, // annotation arguments
        $this->connection, // connection
        empty($args) ? [] : $args,  // external arguments (defined at run time)
        $this // mapper instance
    );
@end
