<?php

it('DEBUG dump sp form', function () {
    $form = $this->get(route('sp.index'))->assertOk()->getContent();
    file_put_contents(sys_get_temp_dir().'/sp_form_dump.html', $form);
    expect(strlen($form))->toBeGreaterThan(1000);
});
