if (empty(self::$loaded[{{@$self->getPath()}}])) {
    if (!class_exists({{@$self->getClass()}}, false)) {
        require __DIR__ . {{@$self->getPath()}};
    }
    self::$loaded[{{@$self->getPath()}}] = true;
}

$args = empty($args) ? [] : $args;

@if ($self->getAnnotation()->has('Embed'))
    {{ $self->toEmbedCode($var) }}
@elif ($self->isMethod())
    @if ($self->isPublic())
        @if ($self->isStatic())
            $return = \{{$self->getClass()}}::{{$self->getMethod()}}(
        @elif ($prop->getClass() == $self->getClass())
            $return = $document->{{$self->getMethod()}}(
        @else
            // Improve me (should construct once and reuse it)
            $return = (new \{{$self->getClass()}})->{{$self->getMethod()}}(
        @end
            {{$var}}, // document variable 
            $args,  // external arguments (defined at run time)
            $this->connection, // connection
            {{@$args}}, // annotation arguments
            $this // mapper instance
        );
    @else
        $reflection = new \ReflectionMethod({{@"\\". $self->getClass()}}, {{@$self->getMethod()}});
        $reflection->setAccessible(true);
        $return = $reflection->invoke(
            {{$var}}, // document variable 
            $args,  // external arguments (defined at run time)
            $this->connection, // connection
            {{@$args}}, // annotation arguments
            $this // mapper instance
        );
    @end
@else
    $return = \{{$self->getFunction()}}(
        {{$var}}, // document variable 
        $args,  // external arguments (defined at run time)
        $this->connection, // connection
        {{@$args}}, // annotation arguments
        $this // mapper instance
    );
@end
