<?php

class Area_Api extends Api
{
	public function getList()
	{
		Out(1, '', load_model('area')->getAll(array(), '', '', true, true));
	}


}