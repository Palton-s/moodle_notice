<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Brazilian Portuguese strings for local_notice.
 *
 * @package    local_notice
 * @copyright  2026
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['acksreset'] = 'A notícia voltará a ser exibida para todos os usuários.';
$string['addnotice'] = 'Adicionar notícia';
$string['anytime'] = 'Imediatamente';
$string['backtonotices'] = 'Voltar para as notícias';
$string['browser'] = 'Navegador / sistema';
$string['buttonlabel'] = 'Texto do botão';
$string['buttonlabel_help'] = 'Texto do botão que fecha a notícia. Deixe em branco para usar "Entendi".';
$string['clearfilters'] = 'Limpar filtros';
$string['closemethod'] = 'Fechada pelo';
$string['closemethod_any'] = 'Qualquer';
$string['closemethod_button'] = 'Botão';
$string['closemethod_dismiss'] = 'X / tecla Esc';
$string['confirmdelete'] = 'Excluir a notícia "{$a}"? Esta ação não pode ser desfeita.';
$string['confirmreset'] = 'Exibir a notícia "{$a}" novamente para todos os usuários, inclusive os que já a fecharam?';
$string['datefrom'] = 'Vista a partir de';
$string['dateto'] = 'Vista até';
$string['defaultbutton'] = 'Entendi';
$string['duration'] = 'Tempo na tela';
$string['editnotice'] = 'Editar notícia';
$string['enabled'] = 'Exibir notícias';
$string['enabled_desc'] = 'Quando desativado, nenhuma notícia é exibida aos usuários, independentemente do status de cada uma.';
$string['errorendbeforestart'] = 'A data final deve ser posterior à data inicial.';
$string['filter'] = 'Filtrar';
$string['filterstatus'] = 'Exibir';
$string['ipaddress'] = 'Endereço IP';
$string['lastaccess'] = 'Último acesso ao site';
$string['lastmodified'] = 'Última modificação';
$string['lessthanasecond'] = 'Menos de 1 segundo';
$string['managenotices'] = 'Gerenciar notícias';
$string['never'] = 'Nunca';
$string['noend'] = 'Sem fim';
$string['nonotices'] = 'Nenhuma notícia ainda. Clique em "Adicionar notícia" para criar a primeira.';
$string['notice:manage'] = 'Gerenciar notícias de login';
$string['notice:viewreport'] = 'Ver quem visualizou as notícias de login';
$string['noticecontent'] = 'Conteúdo';
$string['noticecontent_help'] = 'Monte a notícia com texto, imagens, vídeos, links e arquivos. Ela aparece em uma janela na próxima vez que cada usuário abrir uma página após o login.';
$string['noticedeleted'] = 'Notícia excluída.';
$string['noticeenabled'] = 'Ativa';
$string['noticesaved'] = 'Notícia salva.';
$string['noticetitle'] = 'Título';
$string['notrecorded'] = 'Não registrado (visualizada antes desta informação ser coletada)';
$string['notseen'] = 'Ainda não viu';
$string['period'] = 'Período';
$string['perpage'] = 'Por página';
$string['pluginisdisabled'] = 'As notícias estão desativadas globalmente, então os usuários não verão nenhuma delas. <a href="{$a}">Ative-as nas configurações</a>.';
$string['pluginname'] = 'Notícias de login';
$string['preview'] = 'Pré-visualizar';
$string['privacy:metadata:local_notice'] = 'Notícias criadas pelos administradores.';
$string['privacy:metadata:local_notice:usermodified'] = 'O usuário que modificou a notícia por último.';
$string['privacy:metadata:local_notice_ack'] = 'Registra quais notícias cada usuário fechou, para que não sejam exibidas novamente.';
$string['privacy:metadata:local_notice_ack:closemethod'] = 'Se a notícia foi fechada pelo botão ou pelo X / Esc.';
$string['privacy:metadata:local_notice_ack:duration'] = 'Quanto tempo a notícia ficou na tela.';
$string['privacy:metadata:local_notice_ack:ipaddress'] = 'O endereço IP de onde a notícia foi fechada.';
$string['privacy:metadata:local_notice_ack:noticeid'] = 'A notícia que foi fechada.';
$string['privacy:metadata:local_notice_ack:timecreated'] = 'Quando a notícia foi fechada.';
$string['privacy:metadata:local_notice_ack:useragent'] = 'O navegador e o sistema operacional usados ao fechar a notícia.';
$string['privacy:metadata:local_notice_ack:userid'] = 'O usuário que fechou a notícia.';
$string['resetacks'] = 'Exibir novamente para todos';
$string['resetacksonsave'] = 'Exibir novamente para todos';
$string['resetacksonsave_help'] = 'Se marcado, os usuários que já fecharam esta notícia a verão novamente (útil após mudanças importantes).';
$string['search'] = 'Buscar';
$string['search_placeholder'] = 'Nome, e-mail, usuário, número de identificação ou IP';
$string['seen'] = 'Viu';
$string['seenby'] = 'Visto por';
$string['seenstatus'] = 'Situação';
$string['settings'] = 'Configurações';
$string['status'] = 'Status';
$string['statusactive'] = 'Ativa';
$string['statusall'] = 'Todos os usuários ativos';
$string['statusdisabled'] = 'Desativada';
$string['statusexpired'] = 'Expirada';
$string['statusnotseen'] = 'Usuários que ainda não viram';
$string['statusscheduled'] = 'Agendada';
$string['statusseen'] = 'Usuários que viram';
$string['timeend'] = 'Exibir até';
$string['timeend_help'] = 'Opcional. Depois desta data a notícia deixa de ser exibida.';
$string['timeseen'] = 'Vista em';
$string['timestart'] = 'Exibir a partir de';
$string['timestart_help'] = 'Opcional. A notícia só é exibida a partir desta data.';
$string['user'] = 'Usuário';
$string['viewsfilename'] = 'visualizacoes-noticia';
$string['viewsreport'] = 'Quem viu "{$a}"';
$string['viewssummary'] = '{$a->seen} de {$a->total} usuários ativos viram esta notícia ({$a->percent}%). {$a->notseen} ainda não viram.';
