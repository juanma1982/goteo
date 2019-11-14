pragma solidity ^0.5.0;

import "github.com/OpenZeppelin/zeppelin-solidity/contracts/ownership/Ownable.sol";
import "github.com/OpenZeppelin/zeppelin-solidity/contracts/math/SafeMath.sol";

contract FabricaDeProyecto is Ownable {
    using SafeMath for uint256;
    event NuevoProyecto(uint id, string name, uint amount);
    event ProyectoCancelado(uint id);
    event ProyectoCerrado(uint id);
    enum Estados {CLOSE,OPEN,CANCELED}
    struct Proyecto {
        string name;
        uint amount;
        uint currentAmount;
        uint creationDate;
        uint endDate;
        Estados estado;
        address payable[] contributorsAddresses;
        mapping (address => uint) contributors;
    }
    
    Proyecto[] public proyectos;
    mapping (uint => address payable) public proyectosToOwner;
    mapping (address => uint) ownerProyectosCount;
    
    function createProyecto(string memory _name, uint _amount, uint _days) public {
        address payable[] memory contributors;
        uint id = proyectos.push(Proyecto(_name, _amount, 0, now, uint(now + _days*60*60*24), Estados.OPEN, contributors)) - 1;
        proyectosToOwner[id] = msg.sender;
        ownerProyectosCount[msg.sender]++;
        emit NuevoProyecto(id, _name, _amount);
    }
    
    function contributeProyecto(uint _idProyecto, uint _amount) payable public  {
        require(proyectos[_idProyecto].estado == Estados.OPEN);
        require(msg.value >= _amount);
        proyectos[_idProyecto].currentAmount += _amount;
        proyectos[_idProyecto].contributorsAddresses.push(msg.sender);
        proyectos[_idProyecto].contributors[msg.sender] += _amount;
    }
    
    function verifyProyecto(uint _idProyecto) public {
        Proyecto storage proyecto = proyectos[_idProyecto];
        if (proyecto.currentAmount >= proyecto.amount) {
            proyecto.estado = Estados.CLOSE;
            proyectosToOwner[_idProyecto].transfer(proyecto.currentAmount);
            emit ProyectoCerrado(_idProyecto);
        } else if (proyecto.endDate <= now) {
            proyecto.estado = Estados.CANCELED;
            for (uint i = 0; i < proyecto.contributorsAddresses.length; i++) {
                address payable contributor = proyecto.contributorsAddresses[i];
                contributor.transfer(proyecto.contributors[contributor]);
            }
            emit ProyectoCancelado(_idProyecto);
        }
    }
    
    function getProyectosCount() public view returns(uint){
        return proyectos.length;
    }
    
    function verifyProyectoAmount(uint _idProyecto) public view returns(uint){
        Proyecto memory proyecto = proyectos[_idProyecto];
        require(address(this).balance >= proyecto.currentAmount);
        return proyecto.currentAmount;
    }
}