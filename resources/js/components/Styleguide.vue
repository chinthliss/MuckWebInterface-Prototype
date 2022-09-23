<template>
    <div class="container">
        <div class="row">
            <div class="col">
                <h2>Style Guide</h2>
                <p class="text-muted">Note - this is the proposed styleguide going forwards, not the in-use one. It's also 'hacked' over the top of the existing style so a lot of things, such as mouse-over colors, aren't working correctly here.</p>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <h3>Colors</h3>
                <div class="new p-2">
                    <table class="table table-borderless">
                        <thead>
                        <tr>
                            <th scope="col">Color</th>
                            <th scope="col">Foreground</th>
                            <th scope="col">Outline</th>
                            <th scope="col">Button</th>
                        </tr>
                        </thead>
                        <tr>
                            <th scope="row">
                                <b>Primary</b>
                                <span
                                    class="text-muted small">Main highlighting, button suggestions, etc.</span>
                            </th>
                            <td>
                                <div class="new text-primary">Example</div>
                            </td>
                            <td>
                                <div class="new border border-primary p-1">Example</div>
                            </td>
                            <td>
                                <button class="new btn btn-primary">Example</button>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <b>Secondary</b>
                                <span
                                    class="text-muted small">Alternative highlighting, available buttons, etc.</span>
                            </th>
                            <td>
                                <div class="new text-secondary">Example</div>
                            </td>
                            <td>
                                <div class="new border border-secondary p-1">Example</div>
                            </td>
                            <td>
                                <button class="new btn btn-secondary">Example</button>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <b>Tertiary</b>
                                <span class="text-muted small">Footnotes, quotes, etc.</span>
                            </th>
                            <td>
                                <div class="new text-info">Example</div>
                            </td>
                            <td>
                                <div class="new border border-info p-1">Example</div>
                            </td>
                            <td>
                                <button class="new btn btn-info">Example</button>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <b>Warning</b>
                                <span class="text-muted small">Something requires investigation</span>
                            </th>
                            <td>
                                <div class="new text-warning">Example</div>
                            </td>
                            <td>
                                <div class="new border border-warning p-1">Example</div>
                            </td>
                            <td>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <b>Danger</b>
                                <span class="text-muted small">Something is wrong, errors in forms, etc.</span>
                            </th>
                            <td>
                                <div class="new text-danger">Example</div>
                            </td>
                            <td>
                                <div class="new border border-danger p-1">Example</div>
                            </td>
                            <td>
                            </td>
                        </tr>

                    </table>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <h3>Headers</h3>
                <div class="new p-2">
                    <h1 class="new">Header 1</h1>
                    <h2 class="new">Header 2</h2>
                    <h3 class="new">Header 3</h3>
                    <h4 class="new">Header 4</h4>
                    <h5 class="new">Header 5</h5>
                    <h6 class="new">Header 6</h6>
                </div>
            </div>

            <div class="col-6">
                <h3>Example Body</h3>
                <div class="new p-2">
                    <h2 class="new">Heading</h2>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut velit
                        massa, aliquet eu enim ut, facilisis imperdiet purus. Proin consectetur viverra felis,
                        id
                        condimentum lacus lobortis eu. Vestibulum metus mauris, mattis eget odio ut, commodo
                        imperdiet dolor. Morbi in arcu maximus, ultrices odio a, rutrum velit. Ut porta nulla
                        sed
                        dolor bibendum convallis. Aenean volutpat gravida nulla id accumsan. Fusce eu metus sit
                        amet
                        ante ullamcorper sollicitudin non ut nisl. Fusce convallis mi at lorem commodo blandit.
                        Aliquam tempor odio elit. Sed gravida mi mauris, vel sodales mi elementum maximus.</p>

                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <h3>Example Button group</h3>
                <div class="new p-2">

                    <div class="btn-group" role="group" aria-label="Basic example">
                        <button type="button" class="new btn btn-primary">Click me</button>
                        <button type="button" class="new btn btn-secondary">But maybe me</button>
                        <button type="button" class="new btn btn-secondary">Or I guess me</button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "styleguide",
    props: {},
    data: function () {
        return {}
    },
    methods: {}
}
</script>

<style scoped lang="scss">
$primary: #caFF80;
$secondary: #2BAB5C;
$tertiary: #6EAFA6;
$background: #0e1e0e;

.new {
    background-color: $background;

    &.text-primary {
        color: $primary !important;
    }

    &.btn-primary {
        background-color: $primary !important;
    }

    &.border-primary {
        border-color: $primary !important;
    }

    &.text-secondary {
        color: $secondary !important;
    }

    &.btn-secondary {
        background-color: $secondary !important;
        color: #000000 !important;
    }

    &.border-secondary {
        border-color: $secondary !important;
    }

    &.text-info {
        color: $tertiary !important;
    }

    &.btn-info {
        background-color: $tertiary !important;
        color: #FFFFFF !important;
        border-color: $tertiary !important;
    }

    &.border-info {
        border-color: $tertiary !important;
    }

}

h1, h2, h3, h4, h5, h6 {
    &.new {
        color: $primary;
    }
}


</style>
