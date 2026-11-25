<hw:field name="code" :label="__('Authentication code')">
    <hw:input inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" :old="false" />
</hw:field>
<hw:field name="recovery_code" :label="__('Or a recovery code')">
    <hw:input autocomplete="off" maxlength="100" :old="false" />
    <hw:field.description>{{ __('Provide one code only. Recovery codes can be used once.') }}</hw:field.description>
</hw:field>
