<?php

namespace AppBundle\Controller;

use Novosga\Context;
use Novosga\Http\JsonResponse;
use Novosga\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * HomeController.
 *
 * @author Rogerio Lino <rogeriolino@gmail.com>
 */
class HomeController extends Controller
{
    public function indexAction(Request $request)
    {
    }

    public function unidade(Context $context)
    {
        $response = new JsonResponse();
        $id = (int) $context->request()->post('unidade');
        try {
            if (!$context->request()->isPost()) {
                throw new \Exception(_('Somente via POST'));
            }
            $unidade = $context->database()->createEntityManager()->find("AppBundle\Entity\Unidade", $id);
            $context->getUser()->setUnidade($unidade);
            // atualizando a sessao
            $context->setUser($context->getUser());
            $response->success = true;
        } catch (\Exception $e) {
            $response->message = $e->getMessage();
        }

        return $response;
    }

    public function perfil(Context $context)
    {
        $usuario = $context->getUser();
        $salvo = false;
        // se editando
        if ($context->request()->isPost()) {
            // atualizando sessao
            $usuario->setNome($context->request()->post('nome'));
            $usuario->setSobrenome($context->request()->post('sobrenome'));
            $context->setUser($usuario);
            // atualizando banco
            $query = $context->database()->createEntityManager()->createQuery("
                UPDATE
                    AppBundle\Entity\Usuario e
                SET
                    e.nome = :nome, e.sobrenome = :sobrenome
                WHERE
                    e.id = :id
            ");
            $query->setParameter('id', $usuario->getId());
            $query->setParameter('nome', $usuario->getNome());
            $query->setParameter('sobrenome', $usuario->getSobrenome());
            $query->execute();
            $salvo = true;
        }
        $this->app()->view()->set('salvo', $salvo);
        $this->app()->view()->set('usuario', $usuario);
    }

    public function alterar_senha(Context $context)
    {
        $response = new JsonResponse();
        $usuario = $context->getUser();
        try {
            if (!$usuario) {
                throw new \Exception(_('Nenhum usuário na sessão'));
            }
            $atual = $context->request()->post('atual');
            $senha = $context->request()->post('senha');
            $confirmacao = $context->request()->post('confirmacao');
            $hash = $this->app()->getAcessoService()->verificaSenha($senha, $confirmacao);
            $em = $context->database()->createEntityManager();
            // verificando senha atual
            $query = $em->createQuery("SELECT u.senha FROM AppBundle\Entity\Usuario u WHERE u.id = :id");
            $query->setParameter('id', $usuario->getId());
            $rs = $query->getSingleResult();
            if ($rs['senha'] != Security::passEncode($atual)) {
                throw new \Exception(_('Senha atual não confere'));
            }
            // atualizando o banco
            $query = $em->createQuery("UPDATE AppBundle\Entity\Usuario u SET u.senha = :senha WHERE u.id = :id");
            $query->setParameter('senha', $hash);
            $query->setParameter('id', $usuario->getId());
            $query->execute();
            $response->success = true;
        } catch (\Exception $e) {
            $response->message = $e->getMessage();
        }

        return $response;
    }

    public function desativar_sessao(Context $context)
    {
        $response = new JsonResponse(true);
        $usuario = $context->getUser();
        $usuario->setAtivo(false);
        $context->setUser($usuario);

        return $response;
    }
}