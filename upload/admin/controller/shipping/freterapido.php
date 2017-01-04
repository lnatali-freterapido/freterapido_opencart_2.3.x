<?php
class ControllerShippingFreteRapido extends Controller {
    private $error = array();

    public function install() {
        // Cria a tabela que relaciona as categorias do OpenCart com as do Frete Rápido
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "category_to_fr_category`
            (
                category_id INT(11) NOT NULL,
                fr_category_id INT(11) NOT NULL,
                CONSTRAINT `PRIMARY` PRIMARY KEY (category_id, fr_category_id)
            );
        ");

        // Cria a tabela de categorias do Frete Rápido
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `fr_category`
            (
              fr_category_id INT(11) PRIMARY KEY NOT NULL AUTO_INCREMENT,
              name           VARCHAR(255)        NOT NULL,
              code           SMALLINT(6)         NOT NULL
            );
        ");

        // Limpa os registros da tabela de categorias
        $this->db->query("
            TRUNCATE TABLE fr_category;
        ");

        // Insere novamente as categorias do Frete Rápido
        $this->db->query("
            INSERT INTO fr_category
            (fr_category_id, name, code) VALUES
              (1, 'Abrasivos', 1),
              (2, 'Adubos / Fertilizantes', 2),
              (3, 'Alimentos', 3),
              (4, 'Artigos para Pesca', 4),
              (5, 'Auto Peças', 5),
              (6, 'Bebidas / Destilados', 6),
              (7, 'Brindes', 7),
              (8, 'Brinquedos', 8),
              (9, 'Calçados', 9),
              (10, 'CD / DVD / Blu-Ray', 10),
              (11, 'Combustíveis / Óleos', 11),
              (12, 'Confecção', 12),
              (13, 'Cosméticos / Perfumaria', 13),
              (14, 'Couro', 14),
              (15, 'Derivados Petróleo', 15),
              (16, 'Descartáveis', 16),
              (17, 'Editorial', 17),
              (18, 'Eletrônicos', 18),
              (19, 'Eletrodomésticos', 19),
              (20, 'Embalagens', 20),
              (21, 'Explosivos / Pirotécnicos', 21),
              (22, 'Farmacêutico / Medicamentos', 22),
              (23, 'Ferragens', 23),
              (24, 'Ferramentas', 24),
              (25, 'Fibras Ópticas', 25),
              (26, 'Fonográfico', 26),
              (27, 'Fotográfico', 27),
              (28, 'Fraldas / Geriátricas', 28),
              (29, 'Higiene / Limpeza', 29),
              (30, 'Impressos', 30),
              (31, 'Informática / Computadores', 31),
              (32, 'Instrumento Musical', 32),
              (33, 'Livro(s)', 33),
              (34, 'Materiais Escolares', 34),
              (35, 'Materiais Esportivos', 35),
              (36, 'Materiais Frágeis', 36),
              (37, 'Material de Construção', 37),
              (38, 'Material de Irrigação', 38),
              (39, 'Material Elétrico / Lâmpada(s)', 39),
              (40, 'Material Gráfico', 40),
              (41, 'Material Hospitalar', 41),
              (42, 'Material Odontológico', 42),
              (43, 'Material Pet Shop / Rações', 43),
              (44, 'Material Veterinário', 44),
              (45, 'Móveis / Utensílios', 45),
              (46, 'Moto Peças', 46),
              (47, 'Mudas / Plantas', 47),
              (48, 'Papelaria / Documentos', 48),
              (49, 'Perfumaria', 49),
              (50, 'Material Plástico', 50),
              (51, 'Pneus e Borracharia', 51),
              (52, 'Produtos Cerâmicos', 52),
              (53, 'Produtos Químicos', 53),
              (54, 'Produtos Veterinários', 54),
              (55, 'Revistas', 55),
              (56, 'Sementes', 56),
              (57, 'Suprimentos Agrícolas / Rurais', 57),
              (58, 'Têxtil', 58),
              (59, 'Vacinas', 59),
              (60, 'Vestuário', 60),
              (61, 'Vidros / Frágil', 61),
              (62, 'Cargas refrigeradas/congeladas', 62),
              (63, 'Papelão', 63),
              (64, 'Outros', 999);
        ");

        function hasColumn($db, $table, $column) {
            $query = $db->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME LIKE '" . DB_PREFIX . "$table' AND COLUMN_NAME = '$column'");
            return $query->row ? true : false;
        }

        function addColumn($db, $table, $column, $query) {
            $column = hasColumn($db, $table, $column);

            if (!$column) {
                $db->query($query);
            }
        }

        $db = $this->db;

        // Adiciona a coluna 'manufacturing_deadline' na tabela *_product
        addColumn(
            $db,
            'product',
            'manufacturing_deadline',
            "ALTER TABLE " . DB_PREFIX . "product ADD manufacturing_deadline INT(11) DEFAULT '0' NOT NULL AFTER stock_status_id"
        );
    }

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

        $data['text_correios_valor_declarado'] = $this->language->get('text_correios_valor_declarado');
        $data['text_correios_mao_propria'] = $this->language->get('text_correios_mao_propria');
        $data['text_correios_aviso_recebimento'] = $this->language->get('text_correios_aviso_recebimento');

        $data['text_results_nofilter'] = $this->language->get('text_results_nofilter');
        $data['text_results_cheaper'] = $this->language->get('text_results_cheaper');
        $data['text_results_faster'] = $this->language->get('text_results_faster');

        $data['text_none'] = $this->language->get('text_none');
        $data['text_yes'] = $this->language->get('text_yes');
        $data['text_no'] = $this->language->get('text_no');
        $data['text_select_all'] = $this->language->get('text_select_all');
        $data['text_unselect_all'] = $this->language->get('text_unselect_all');

        $data['entry_freterapido_token'] = $this->language->get('entry_freterapido_token');
        $data['entry_freterapido_token_code'] = $this->language->get('entry_freterapido_token_code');
        $data['entry_cost'] = $this->language->get('entry_cost');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $data['entry_postcode']= $this->language->get('entry_postcode');
        $data['entry_cnpj']= $this->language->get('entry_cnpj');
        $data['entry_ie']= $this->language->get('entry_ie');
        $data['entry_results']= $this->language->get('entry_results');
        $data['entry_limit']= $this->language->get('entry_limit');
        $data['entry_post_deadline']= $this->language->get('entry_post_deadline');
        $data['entry_post_cost']= $this->language->get('entry_post_cost');

        $data['help_freterapido_token'] = $this->language->get('help_freterapido_token');
        $data['help_post_deadline'] = $this->language->get('help_post_deadline');
        $data['help_post_cost'] = $this->language->get('help_post_cost');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['tab_general'] = $this->language->get('tab_general');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
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

        if (isset($this->error['postcode'])) {
            $data['error_postcode'] = $this->error['postcode'];
        } else {
            $data['error_postcode'] = '';
        }

        if (isset($this->error['token'])) {
            $data['error_token'] = $this->error['token'];
        } else {
            $data['error_token'] = '';
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

        if(version_compare(VERSION, '2.2.0.0', '>')) {
            $data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=shipping', true);
        } else {
            $data['cancel'] = $this->url->link('extension/shipping', 'token=' . $this->session->data['token'], true);
        }

        if (isset($this->request->post['freterapido_status'])) {
            $data['freterapido_status'] = $this->request->post['freterapido_status'];
        } else {
            $data['freterapido_status'] = $this->config->get('freterapido_status');
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

        if (isset($this->request->post['freterapido_correios_valor_declarado'])) {
            $data['freterapido_correios_valor_declarado'] = $this->request->post['freterapido_correios_valor_declarado'];
        } else {
            $data['freterapido_correios_valor_declarado'] = $this->config->get('freterapido_correios_valor_declarado');
        }

        if (isset($this->request->post['freterapido_correios_mao_propria'])) {
            $data['freterapido_correios_mao_propria'] = $this->request->post['freterapido_correios_mao_propria'];
        } else {
            $data['freterapido_correios_mao_propria'] = $this->config->get('freterapido_correios_mao_propria');
        }

        if (isset($this->request->post['freterapido_correios_aviso_recebimento'])) {
            $data['freterapido_correios_aviso_recebimento'] = $this->request->post['freterapido_correios_aviso_recebimento'];
        } else {
            $data['freterapido_correios_aviso_recebimento'] = $this->config->get('freterapido_correios_aviso_recebimento');
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

        if (isset($this->request->post['freterapido_results'])) {
            $data['freterapido_results'] = $this->request->post['freterapido_results'];
        } else {
            $data['freterapido_results'] = $this->config->get('freterapido_results');
        }

        if (isset($this->request->post['freterapido_limit'])) {
            $data['freterapido_limit'] = $this->request->post['freterapido_limit'];
        } else {
            $data['freterapido_limit'] = $this->config->get('freterapido_limit');
        }

        if (isset($this->request->post['freterapido_postcode'])) {
            $data['freterapido_postcode'] = $this->request->post['freterapido_postcode'];
        } else {
            $data['freterapido_postcode'] = $this->config->get('freterapido_postcode');
        }

        if (isset($this->request->post['freterapido_msg_prazo'])) {
            $data['freterapido_msg_prazo'] = $this->request->post['freterapido_msg_prazo'];
        } else {
            $data['freterapido_msg_prazo'] = $this->config->get('freterapido_msg_prazo');
        }

        if (isset($this->request->post['freterapido_token'])) {
            $data['freterapido_token'] = $this->request->post['freterapido_token'];
        } else {
            $data['freterapido_token'] = $this->config->get('freterapido_token');
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

        if (!$this->request->post['freterapido_cnpj']) {
            $this->error['cnpj'] = $this->language->get('error_cnpj');
        }

        if (!$this->request->post['freterapido_ie']) {
            $this->error['ie'] = $this->language->get('error_ie');
        }

        if (!$this->request->post['freterapido_postcode']) {
            $this->error['postcode'] = $this->language->get('error_postcode');
        }

        if (!$this->request->post['freterapido_token']) {
            $this->error['token'] = $this->language->get('error_token');
        }

        return !$this->error;
    }
}
