<?php

test('the application opens member management', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('members.index'));
});
