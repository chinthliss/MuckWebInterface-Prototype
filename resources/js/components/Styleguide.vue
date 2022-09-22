<template>
    <div class="container">
        <div class="row">
            <div class="col">
                <h2>Style Guide</h2>
                <p class="text-muted">Note - this is the proposed styleguide going forwards, not the in-use one.</p>

                <div class="row">
                    <div class="col-6">
                        <h3>Headers</h3>
                        <h1 class="new">Header 1</h1>
                        <h2 class="new">Header 2</h2>
                        <h3 class="new">Header 3</h3>
                        <h4 class="new">Header 4</h4>
                        <h5 class="new">Header 5</h5>
                        <h6 class="new">Header 6</h6>
                    </div>

                    <div class="col-6">
                        <h3>Body</h3>
                        <p class="new">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut velit massa, aliquet eu enim ut, facilisis imperdiet purus. Proin consectetur viverra felis, id condimentum lacus lobortis eu. Vestibulum metus mauris, mattis eget odio ut, commodo imperdiet dolor. Morbi in arcu maximus, ultrices odio a, rutrum velit. Ut porta nulla sed dolor bibendum convallis. Aenean volutpat gravida nulla id accumsan. Fusce eu metus sit amet ante ullamcorper sollicitudin non ut nisl. Fusce convallis mi at lorem commodo blandit. Aliquam tempor odio elit. Sed gravida mi mauris, vel sodales mi elementum maximus.</p>
                    </div>

                </div>

                <div class="row">
                    <div class="col">
                        <h3>Colors</h3>
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
                                    <span class="text-muted small">Main highlighting, button suggestions, etc.</span>
                                </th>
                                <td><div class="new text-primary">Example</div></td>
                                <td><div class="new border border-primary p-1">Example</div></td>
                                <td><button class="new btn btn-primary">Example</button></td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <b>Secondary</b>
                                    <span class="text-muted small">Alternative highlighting, available buttons, etc.</span>
                                </th>
                                <td><div class="new text-secondary">Example</div></td>
                                <td><div class="new border border-secondary p-1">Example</div></td>
                                <td><button class="new btn btn-secondary">Example</button></td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <b>Tertiary</b>
                                    <span class="text-muted small">Footnotes, quotes, etc.</span>
                                </th>
                                <td><div class="new text-info">Example</div></td>
                                <td><div class="new border border-info p-1">Example</div></td>
                                <td><button class="new btn btn-info">Example</button></td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <b>Warning</b>
                                    <span class="text-muted small">Something requires investigation</span>
                                </th>
                                <td><div class="new text-warning">Example</div></td>
                                <td><div class="new border border-warning p-1">Example</div></td>
                                <td><button class="new btn btn-warning">Example</button></td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <b>Danger</b>
                                    <span class="text-muted small">Something is wrong, errors in forms, etc.</span>
                                </th>
                                <td><div class="new text-danger">Example</div></td>
                                <td><div class="new border border-danger p-1">Example</div></td>
                                <td><button class="new btn btn-danger">Example</button></td>
                            </tr>

                        </table>
                    </div>
                </div>




            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "styleguide",
    props: {
    },
    data: function () {
        return {

        }
    },
    methods: {
    }
}
</script>
