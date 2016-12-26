<?php
class ControllerShippingfreterapido extends Controller {
	private $error = array(); 
	
	public function index() {   
		$this->load->language('shipping/freterapido');
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('freterapido', $this->request->post);		
			
			$this->session->data['success'] = $this->language->get('text_success');

            if(version_compare(VERSION, '2.2.0.0', '>')) {
                $this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL'));
            } else {
			    $this->response->redirect($this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL'));
            }
		}
		
		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		// correios
		$data['text_correios_valor_declarado'] = $this->language->get('text_correios_valor_declarado');
		$data['text_correios_mao_propria'] = $this->language->get('text_correios_mao_propria');
		$data['text_correios_aviso_recebimento'] = $this->language->get('text_correios_aviso_recebimento');
		// results
		$data['text_results_nofilter'] = $this->language->get('text_results_nofilter');
		$data['text_results_cheaper'] = $this->language->get('text_results_cheaper');
		$data['text_results_faster'] = $this->language->get('text_results_faster');

		$data['text_none'] = $this->language->get('text_none');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_select_all'] = $this->language->get('text_select_all');
		$data['text_unselect_all'] = $this->language->get('text_unselect_all');

        $data['entry_msg_prazo'] = $this->language->get('entry_msg_prazo');

        $data['entry_freterapido_token'] = $this->language->get('entry_freterapido_token');
        $data['entry_freterapido_token_codigo'] = $this->language->get('entry_freterapido_token_codigo');
        // $data['entry_freterapido_key_senha'] = $this->language->get('entry_freterapido_key_senha');

		$data['entry_cost'] = $this->language->get('entry_cost');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		//helpers
		$data['help_freterapido_token'] = $this->language->get('help_freterapido_token');
        $data['help_msg_prazo'] = $this->language->get('help_msg_prazo');
        $data['help_post_deadline'] = $this->language->get('help_post_deadline');
        $data['help_post_cost'] = $this->language->get('help_post_cost');
		
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		
		$data['tab_general'] = $this->language->get('tab_general');
		
		$data['entry_postcode']= $this->language->get('entry_postcode');

		$data['entry_cnpj']= $this->language->get('entry_cnpj');

		$data['entry_ie']= $this->language->get('entry_ie');

		$data['entry_results']= $this->language->get('entry_results');

		$data['entry_limit']= $this->language->get('entry_limit');

		$data['entry_post_deadline']= $this->language->get('entry_post_deadline');

		$data['entry_post_cost']= $this->language->get('entry_post_cost');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		if (isset($this->error['postcode'])) {
			$data['error_postcode'] = $this->error['postcode'];
		} else {
			$data['error_postcode'] = '';
		}
		if (isset($this->error['cnpj'])) {
			$data['error_cnpj'] = $this->error['cnpj'];
		} else {
			$data['error_cnpj'] = '';
		}
		if (isset($this->error['ie'])) {
			$data['error_ie'] = $this->error['ie'];
		} else {
			$data['error_ie'] = '';
		}

		$data['breadcrumbs'] = array();
   		
   		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
   		);
   		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_shipping'),
			'href' => $this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL')
   		);
   		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('shipping/freterapido', 'token=' . $this->session->data['token'], 'SSL')
   		);
		
   		$data['action'] = $this->url->link('shipping/freterapido', 'token=' . $this->session->data['token'], 'SSL');
		
   		$data['cancel'] = $this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL');
		
		if (isset($this->request->post['freterapido_status'])) {
			$data['freterapido_status'] = $this->request->post['freterapido_status'];
		} else {
			$data['freterapido_status'] = $this->config->get('freterapido_status');
		}

		if (isset($this->request->post['freterapido_postcode'])) {
			$data['freterapido_postcode'] = $this->request->post['freterapido_postcode'];
		} else {
			$data['freterapido_postcode'] = $this->config->get('freterapido_postcode');
		}
		if (isset($this->request->post['freterapido_cnpj'])) {
			$data['freterapido_cnpj'] = $this->request->post['freterapido_cnpj'];
		} else {
			$data['freterapido_cnpj'] = $this->config->get('freterapido_cnpj');
		}
		if (isset($this->request->post['freterapido_ie'])) {
			$data['freterapido_ie'] = $this->request->post['freterapido_ie'];
		} else {
			$data['freterapido_ie'] = $this->config->get('freterapido_ie');
		}
		if (isset($this->request->post['freterapido_post_deadline'])) {
			$data['freterapido_post_deadline'] = $this->request->post['freterapido_post_deadline'];
		} else {
			$data['freterapido_post_deadline'] = $this->config->get('freterapido_post_deadline');
		}
		if (isset($this->request->post['freterapido_post_cost'])) {
			$data['freterapido_post_cost'] = $this->request->post['freterapido_post_cost'];
		} else {
			$data['freterapido_post_cost'] = $this->config->get('freterapido_post_cost');
		}
        if (isset($this->request->post['freterapido_msg_prazo'])) {
            $data['freterapido_msg_prazo'] = $this->request->post['freterapido_msg_prazo'];
        } else {
            $data['freterapido_msg_prazo'] = $this->config->get('freterapido_msg_prazo');
        }

		if (isset($this->request->post['freterapido_contrato_codigo'])) {
			$data['freterapido_contrato_codigo'] = $this->request->post['freterapido_contrato_codigo'];
		} else {
			$data['freterapido_contrato_codigo'] = $this->config->get('freterapido_contrato_codigo');
		}
		if (isset($this->request->post['freterapido_contrato_senha'])) {
			$data['freterapido_contrato_senha'] = $this->request->post['freterapido_contrato_senha'];
		} else {
			$data['freterapido_contrato_senha'] = $this->config->get('freterapido_contrato_senha');
		}						

		if (isset($this->request->post['freterapido_sort_order'])) {
			$data['freterapido_sort_order'] = $this->request->post['freterapido_sort_order'];
		} else {
			$data['freterapido_sort_order'] = $this->config->get('freterapido_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		if (version_compare(VERSION, '2.2') < 0) {
			$this->response->setOutput($this->load->view('shipping/freterapido.tpl', $data));
		} else {
			$this->response->setOutput($this->load->view('shipping/freterapido', $data));
		}
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'shipping/freterapido')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
?>
