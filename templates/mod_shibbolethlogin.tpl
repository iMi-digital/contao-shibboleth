
<!-- indexer::stop -->
<div class="<?php echo $this->class; ?> block"<?php echo $this->cssID; ?><?php if ($this->style): ?> style="<?php echo $this->style; ?>"<?php endif; ?>>
<?php if ($this->headline): ?>

<<?php echo $this->hl; ?>><?php echo $this->headline; ?></<?php echo $this->hl; ?>>
<?php endif; ?>
<?php if ($this->hasError): ?>
<p class="message error"><?php echo $this->message; ?></p>
<?php endif; ?>

<form action="<?php echo $this->action; ?>" method="post">
<div class="formbody">
<input type="hidden" name="FORM_SUBMIT" value="<?php echo $this->formSubmit; ?>" />
<input type="submit" class="submit" value="<?php echo $this->slabel; ?>" />
</div>
</form>

</div>
<!-- indexer::continue -->